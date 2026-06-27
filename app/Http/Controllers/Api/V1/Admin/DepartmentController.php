<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::query();

        // 🛡️ ZERO-TRUST SCOPING: Employee ko sirf uski company dikhegi
        if (!$context->is_god) {
            $query->where(function ($q) use ($context) {
                $q->whereNull('company_ids')
                    ->orWhereJsonContains('company_ids', 'all')
                    ->orWhereJsonContains('company_ids', (string)$context->company_id)
                    ->orWhereJsonContains('company_ids', (int)$context->company_id);
            });
        }

        // 1. COMPANY JSON FILTER 
        if ($request->filled('company_ids')) {
            $cIds = explode(',', $request->company_ids);

            $query->where(function ($q) use ($cIds) {
                $q->whereJsonContains('company_ids', 'all');
                foreach ($cIds as $cId) {
                    $q->orWhereJsonContains('company_ids', (string)$cId)
                        ->orWhereJsonContains('company_ids', (int)$cId);
                }
            });
        }

        // 2. BRANCH & HO JSON FILTER 
        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $normalBranchIds = [];
            $hoCompanyIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_') || $bId === 'null' || $bId === '') {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId);
                } else {
                    $normalBranchIds[] = $bId;
                }
            }

            if (count($normalBranchIds) > 0 || count($hoCompanyIds) > 0) {
                $query->where(function ($q) use ($normalBranchIds, $hoCompanyIds) {
                    if (count($normalBranchIds) > 0) {
                        foreach ($normalBranchIds as $nId) {
                            $q->orWhereJsonContains('branch_ids', (string)$nId)
                                ->orWhereJsonContains('branch_ids', (int)$nId);
                        }
                    }
                    if (count($hoCompanyIds) > 0) {
                        $q->orWhere(function ($subQ) use ($hoCompanyIds) {
                            $subQ->whereNull('branch_ids')
                                ->orWhereJsonContains('branch_ids', null)
                                ->where(function ($companySubQ) use ($hoCompanyIds) {
                                    $companySubQ->whereJsonContains('company_ids', 'all');
                                    foreach ($hoCompanyIds as $hoCId) {
                                        if (!empty($hoCId)) {
                                            $companySubQ->orWhereJsonContains('company_ids', (string)$hoCId)
                                                ->orWhereJsonContains('company_ids', (int)$hoCId);
                                        }
                                    }
                                });
                        });
                    }
                });
            } else {
                $query->where('id', '<', 0);
            }
        }

        $totalData = Department::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $query->withCount('designations');
        $departments = $query->latest()->get();

        $companiesList = Company::pluck('company_name', 'id')->toArray();

        $data = $departments->map(function ($d) use ($companiesList) {
            $cIds = $d->company_ids ?? [];
            if (empty($cIds) || in_array('all', $cIds)) {
                $d->company_name = 'All Companies (Global)';
            } else {
                $names = [];
                foreach ($cIds as $id) {
                    if (isset($companiesList[$id])) $names[] = $companiesList[$id];
                }
                $d->company_name = !empty($names) ? implode(', ', $names) : 'Unknown';
            }
            $d->designation_count = $d->designations_count ?? 0;
            return $d;
        });

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data"            => $data
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['department_name' => 'required|string|max:255', 'company_ids' => 'nullable|array']);

        // Check Permissions Based on Slugs
        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        $hasDirect = $context->is_god || in_array('department_add_direct', $userPerms);
        $hasRequest = in_array('department_add_request', $userPerms);

        if (!$hasDirect && !$hasRequest && !$context->is_god) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to add departments!'], 403);
        }

        DB::beginTransaction();
        try {
            $finalCompanyIds = $request->company_ids;
            if (!$context->is_god) {
                $finalCompanyIds = [(string)$context->company_id];
            }

            // Head Office ya Branch logic
            $finalBranchIds = $request->branch_ids ?? [];
            if (in_array('all', $finalBranchIds) || empty($finalBranchIds)) {
                $finalBranchIds = null;
            }

            // Status decision (Maker-Checker)
            $deptStatus = $hasDirect ? ($request->status ?? 'active') : 'pending';

            $department = Department::create([
                'department_name' => $request->department_name,
                'company_ids'     => empty($finalCompanyIds) ? null : $finalCompanyIds,
                'branch_ids'      => $finalBranchIds,
                'status'          => $deptStatus,
            ]);

            // Save Designations with syncing status
            if ($request->has('designations')) {
                foreach (json_decode($request->designations, true) as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        // Agar request department_add_request ki hai, to designations bhi pending rahenge
                        $desigStatus = $hasDirect ? ($desig['status'] ?? 'active') : 'pending';

                        $department->designations()->create([
                            'designation_name' => $desig['name'],
                            'designation_code' => strtoupper($desig['code']),
                            'status' => $desigStatus
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => !$hasDirect ? 'Department Requested & is Pending Approval!' : 'Department Saved Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $department = Department::with('designations')->findOrFail($id);

        if (!$context->is_god) {
            $cIds = $department->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
            if (!$isGlobal && !$belongsToCompany) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }
        return response()->json(['status' => 'success', 'data' => $department]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $request->validate(['department_name' => 'required|string|max:255']);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        $canEdit = $context->is_god || in_array('department_edit', $userPerms);

        if (!$canEdit) return response()->json(['status' => 'error', 'message' => 'Unauthorized to edit!'], 403);

        DB::beginTransaction();
        try {
            $department = Department::findOrFail($id);

            if (!$context->is_god) {
                $cIds = $department->company_ids ?? [];
                $isGlobal = empty($cIds) || in_array('all', $cIds);
                if ($isGlobal) return response()->json(['status' => 'error', 'message' => 'Global Departments can only be modified by Master Admins.'], 403);

                $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
                if (!$belongsToCompany) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }

            $finalCompanyIds = $request->company_ids;
            if (!$context->is_god) $finalCompanyIds = [(string)$context->company_id];

            $finalBranchIds = $request->branch_ids ?? [];
            if (in_array('all', $finalBranchIds) || empty($finalBranchIds)) {
                $finalBranchIds = null;
            }

            // Agar department edit ho raha hai aur already pending hai, to pending hi rakhna padega unless approval route hit ho
            $currentStatus = $request->status ?? $department->status;

            $department->update([
                'department_name' => $request->department_name,
                'company_ids'     => empty($finalCompanyIds) ? null : $finalCompanyIds,
                'branch_ids'      => $finalBranchIds,
                'status'          => $currentStatus,
            ]);

            // Syncing Designations
            if ($request->has('designations')) {
                $designationsData = json_decode($request->designations, true);
                $existingIds = [];

                foreach ($designationsData as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        // Priority to incoming status, fallback to active
                        $desigStatus = $desig['status'] ?? 'active';

                        if (isset($desig['id']) && $desig['id'] != '') {
                            $designation = Designation::find($desig['id']);
                            if ($designation) {
                                $designation->update([
                                    'designation_name' => $desig['name'],
                                    'designation_code' => strtoupper($desig['code']),
                                    'status' => $desigStatus
                                ]);
                                $existingIds[] = $designation->id;
                            }
                        } else {
                            $newDesig = $department->designations()->create([
                                'designation_name' => $desig['name'],
                                'designation_code' => strtoupper($desig['code']),
                                'status' => $desigStatus
                            ]);
                            $existingIds[] = $newDesig->id;
                        }
                    }
                }
                $department->designations()->whereNotIn('id', $existingIds)->delete();
            }

            // Agar department 'inactive' kiya gaya manually edit se, to related designations bhi inactivate karna chahiye
            if ($currentStatus === 'inactive') {
                $department->designations()->update(['status' => 'inactive']);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Department Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $department = Department::findOrFail($id);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        if (!$context->is_god && !in_array('department_delete', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to delete!'], 403);
        }

        if (!$context->is_god) {
            $cIds = $department->company_ids ?? [];
            if (empty($cIds) || in_array('all', $cIds)) return response()->json(['status' => 'error', 'message' => 'Global Departments can only be deleted by Master.'], 403);

            $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
            if (!$belongsToCompany) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $department->delete(); // designations cascade delete hongi database constraints se
        return response()->json(['status' => 'success', 'message' => 'Department Deleted!']);
    }

    // ==========================================
    // BULK DELETE ACTION (Select All check box ke liye)
    // ==========================================
    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['ids' => 'required|array']);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        if (!$context->is_god && !in_array('department_delete', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to delete!'], 403);
        }

        // Scope Checking for safety
        $departments = Department::whereIn('id', $request->ids)->get();
        foreach ($departments as $dept) {
            if (!$context->is_god) {
                $cIds = $dept->company_ids ?? [];
                $isGlobal = empty($cIds) || in_array('all', $cIds);
                $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
                if ($isGlobal || !$belongsToCompany) {
                    return response()->json(['status' => 'error', 'message' => 'Cannot bulk delete out of scope departments!'], 403);
                }
            }
        }

        Department::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected Departments Deleted!']);
    }

    // ==========================================
    // APPROVE DEPARTMENT (Maker Checker)
    // ==========================================
    public function approve($id)
    {
        $context = $this->getGlobalContext();
        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

        if (!$context->is_god && !in_array('department_appr', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to Approve!'], 403);
        }

        $department = Department::findOrFail($id);

        DB::beginTransaction();
        try {
            $department->update(['status' => 'active']);
            // Cascade status to designations (Sirf jo pending the)
            $department->designations()->where('status', 'pending')->update(['status' => 'active']);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Department & Designations Approved!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Error approving department'], 500);
        }
    }

    // ==========================================
    // REJECT DEPARTMENT (Maker Checker)
    // ==========================================
    public function reject($id)
    {
        $context = $this->getGlobalContext();
        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

        if (!$context->is_god && !in_array('department_rej', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to Reject!'], 403);
        }

        $department = Department::findOrFail($id);

        DB::beginTransaction();
        try {
            // Reject karne par inactive set karna standard hai
            $department->update(['status' => 'inactive']);
            // Cascade status to designations
            $department->designations()->update(['status' => 'inactive']);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Department Rejected & Inactivated!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Error rejecting department'], 500);
        }
    }

    // ----------------------------------------------------
    // DROPDOWNS API
    // ----------------------------------------------------
    public function getActiveDepartments(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::with(['designations' => function ($q) {
            $q->where('status', 'active');
        }])->where('status', 'active');

        $companyId = $context->is_god ? $request->company_id : $context->company_id;

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_ids')->orWhereJsonContains('company_ids', 'all')->orWhereJsonContains('company_ids', (string)$companyId)->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    // 🔥 FIX: Added Branch Scoping check and Global Context to fix cascading issue
   
    
    public function getDepartmentsByCompany(Request $request)
    {
        $companyId = $request->company_id;
        $branchId = $request->branch_id;

        $query = Department::where('status', 'active');

        // 1. Company Filter: Global departments (null/'all') ya us specific company ke departments
        if (!empty($companyId)) {
            $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }

        // 2. Branch/HO Filter: STRICT LOGIC
        if (!empty($branchId) && $branchId !== 'null') {
            if ($branchId === 'HO' || str_starts_with($branchId, 'HO_')) {
                // Agar Head Office hai, toh sirf wahi departments lao jinka branch_ids null hai
                $query->where(function($q) {
                    $q->whereNull('branch_ids')
                      ->orWhereJsonContains('branch_ids', null);
                });
            } else {
                // Agar normal branch hai, toh us branch ke departments lao
                $query->where(function ($q) use ($branchId) {
                    $q->whereNull('branch_ids')
                      ->orWhereJsonContains('branch_ids', 'all')
                      ->orWhereJsonContains('branch_ids', (string)$branchId)
                      ->orWhereJsonContains('branch_ids', (int)$branchId);
                });
            }
        }

        $departments = $query->orderBy('department_name', 'asc')->get(['id', 'department_name']);

        return response()->json(['status' => 'success', 'data' => $departments]);
    }

    
    public function getBranchesByCompanies(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = \App\Models\Branch::where('branch_status', 'active');

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        } else {
            if ($request->has('company_ids') && is_array($request->company_ids) && count($request->company_ids) > 0) {
                if (!in_array('all', $request->company_ids)) {
                    $query->whereIn('company_id', $request->company_ids);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(['id', 'branch_name', 'branch_id', 'company_id'])
        ]);
    }

    public function getPendingRequests(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::with('designations')->where('status', 'pending')->latest();

        if (!$context->is_god) {
            $query->where(function ($q) use ($context) {
                $q->whereNull('company_ids')
                    ->orWhereJsonContains('company_ids', 'all')
                    ->orWhereJsonContains('company_ids', (string)$context->company_id)
                    ->orWhereJsonContains('company_ids', (int)$context->company_id);
            });
        }

        if ($request->has('company_id') && $request->company_id != '') {
            $compId = $request->company_id;
            $query->where(function ($q) use ($compId) {
                $q->whereJsonContains('company_ids', (string)$compId)
                    ->orWhereJsonContains('company_ids', (int)$compId)
                    ->orWhereJsonContains('company_ids', 'all');
            });
        }

        $totalData = Department::where('status', 'pending')->count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $departments = $query->get();
        $companiesList = Company::pluck('company_name', 'id')->toArray();

        $data = $departments->map(function ($d) use ($companiesList) {
            $cIds = $d->company_ids ?? [];
            if (empty($cIds) || in_array('all', $cIds)) {
                $d->company_name = 'All Companies (Global)';
            } else {
                $names = [];
                foreach ($cIds as $id) if (isset($companiesList[$id])) $names[] = $companiesList[$id];
                $d->company_name = !empty($names) ? implode(', ', $names) : 'Unknown';
            }
            $d->designation_count = $d->designations->count();
            return $d;
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }
}
