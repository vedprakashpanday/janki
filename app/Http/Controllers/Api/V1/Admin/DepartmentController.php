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
        $query = Department::query();

        // 🔥 1. COMPANY JSON FILTER 🔥
        if ($request->filled('company_ids')) {
            $cIds = explode(',', $request->company_ids);
            
            $query->where(function($q) use ($cIds) {
                $q->whereJsonContains('company_ids', 'all');
                foreach ($cIds as $cId) {
                    $q->orWhereJsonContains('company_ids', (string)$cId)
                      ->orWhereJsonContains('company_ids', (int)$cId);
                }
            });
        }

        // 🔥 2. BRANCH & HO JSON FILTER 🔥
        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $normalBranchIds = [];
            $hoCompanyIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId); 
                } else {
                    $normalBranchIds[] = $bId; 
                }
            }

            if (count($normalBranchIds) > 0 || count($hoCompanyIds) > 0) {
                $query->where(function($q) use ($normalBranchIds, $hoCompanyIds) {
                    if (count($normalBranchIds) > 0) {
                        foreach ($normalBranchIds as $nId) {
                            $q->orWhereJsonContains('branch_ids', (string)$nId)
                              ->orWhereJsonContains('branch_ids', (int)$nId);
                        }
                    }
                    if (count($hoCompanyIds) > 0) {
                        $q->orWhere(function($subQ) use ($hoCompanyIds) {
                            $subQ->whereNull('branch_ids')
                                 ->where(function($companySubQ) use ($hoCompanyIds) {
                                     $companySubQ->whereJsonContains('company_ids', 'all');
                                     foreach ($hoCompanyIds as $hoCId) {
                                         $companySubQ->orWhereJsonContains('company_ids', (string)$hoCId)
                                                     ->orWhereJsonContains('company_ids', (int)$hoCId);
                                     }
                                 });
                        });
                    }
                });
            } else {
                $query->where('id', '<', 0); 
            }
        }

        // 🔥 3. DATATABLES SERVER-SIDE PAGINATION & MAPPING 🔥
        $totalData = Department::count();
        $totalFiltered = $query->count();

        // Apply DataTables Pagination
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        // Get count of designations automatically
        $query->withCount('designations');
        
        $departments = $query->get();

        // Fetch Companies to map the names
        $companiesList = Company::pluck('company_name', 'id')->toArray();

        // Map data exactly as frontend expects
        $data = $departments->map(function ($d) use ($companiesList) {
            // Map Company Name
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
            
            // Map Designation Count
            $d->designation_count = $d->designations_count ?? 0;
            
            return $d;
        });

        // Return DataTables standard JSON response
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

        // Check Add Power
        $hasDirect = $context->is_god || $context->is_director;
        if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
            if (in_array('department_add_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
        }

        DB::beginTransaction();
        try {
            // Security: Normal user doosri company assign nahi kar sakta
            $finalCompanyIds = $request->company_ids;
            if (!$context->is_god) {
                $finalCompanyIds = [(string)$context->company_id];
            }

            $department = Department::create([
                'department_name' => $request->department_name,
                'company_ids'     => empty($finalCompanyIds) ? null : $finalCompanyIds,
                'status'          => $hasDirect ? ($request->status ?? 'active') : 'pending',
            ]);

            if ($request->has('designations')) {
                foreach (json_decode($request->designations, true) as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        $department->designations()->create(['designation_name' => $desig['name'], 'designation_code' => strtoupper($desig['code']), 'status' => 'active']);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => !$hasDirect ? 'Department Requested!' : 'Department Saved!']);
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

            $hasDirect = $context->is_god || $context->is_director;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                if (in_array('department_edit_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
            }

            $finalCompanyIds = $request->company_ids;
            if (!$context->is_god) $finalCompanyIds = [(string)$context->company_id];

            $department->update([
                'department_name' => $request->department_name,
                'company_ids'     => empty($finalCompanyIds) ? null : $finalCompanyIds,
                'status'          => $hasDirect ? ($request->status ?? 'active') : 'pending',
            ]);

            if ($request->has('designations')) {
                $designationsData = json_decode($request->designations, true);
                $existingIds = [];
                foreach ($designationsData as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        if (isset($desig['id']) && $desig['id'] != '') {
                            $designation = Designation::find($desig['id']);
                            if ($designation) {
                                $designation->update(['designation_name' => $desig['name'], 'designation_code' => strtoupper($desig['code'])]);
                                $existingIds[] = $designation->id;
                            }
                        } else {
                            $newDesig = $department->designations()->create(['designation_name' => $desig['name'], 'designation_code' => strtoupper($desig['code']), 'status' => 'active']);
                            $existingIds[] = $newDesig->id;
                        }
                    }
                }
                $department->designations()->whereNotIn('id', $existingIds)->delete();
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

        if (!$context->is_god) {
            $cIds = $department->company_ids ?? [];
            if (empty($cIds) || in_array('all', $cIds)) return response()->json(['status' => 'error', 'message' => 'Global Departments can only be deleted by Master.'], 403);

            $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
            if (!$belongsToCompany) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $department->delete();
        return response()->json(['status' => 'success', 'message' => 'Department Deleted!']);
    }

    // 🔴 FOR DROPDOWNS 🔴
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

    public function getDepartmentsByCompany(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::where('status', 'active');
        $companyId = $context->is_god ? $request->company_id : $context->company_id;

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_ids')->orWhereJsonContains('company_ids', 'all')->orWhereJsonContains('company_ids', (string)$companyId)->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'department_name'])]);
    }

// ==========================================
    // 🔴 MISSING FUNCTION: Get Branches for Dropdown 
    // ==========================================
    public function getBranchesByCompanies(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = \App\Models\Branch::where('branch_status', 'active');

        // 🛡️ SECURITY: Agar normal user/director hai toh sirf uski hi company ke branches aayenge
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        } else {
            // Agar Master Admin hai aur usne multiple companies select ki hain
            if ($request->has('company_ids') && is_array($request->company_ids) && count($request->company_ids) > 0) {
                $query->whereIn('company_id', $request->company_ids);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(['id', 'branch_name', 'branch_id', 'company_id'])
        ]);
    }


// ==========================================
    // 🔥 PENDING REQUESTS (MAKER-CHECKER) 🔥
    // ==========================================
   // ==========================================
    // 🔥 PENDING REQUESTS (MAKER-CHECKER) 🔥
    // ==========================================
    public function getPendingRequests(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::with('designations')->where('status', 'pending')->latest();

        // 🛡️ ZERO-TRUST SCOPING BASED ON CONTEXT
        if (!$context->is_god) {
            $query->where(function ($q) use ($context) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$context->company_id)
                  ->orWhereJsonContains('company_ids', (int)$context->company_id);
            });
        }

        // 🔥 FIX: Apply frontend Company Filter 🔥
        if ($request->has('company_id') && $request->company_id != '') {
            $compId = $request->company_id;
            $query->where(function ($q) use ($compId) {
                $q->whereJsonContains('company_ids', (string)$compId)
                  ->orWhereJsonContains('company_ids', (int)$compId)
                  ->orWhereJsonContains('company_ids', 'all');
            });
        }

        $totalData = Department::where('status', 'pending')->count();

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('department_name', 'LIKE', "%{$search}%");
        }

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

    
    // ==========================================
    // 🔥 APPROVE / REJECT ACTION 🔥
    // ==========================================
    public function updateStatus(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $request->validate(['status' => 'required|in:active,inactive']);
        
        // 🛡️ MAKER-CHECKER POWER CHECK
        $hasPower = $context->is_god || $context->is_director;
        if (!$hasPower && method_exists(auth()->user(), 'getAllPermissions')) {
            $perms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            if (in_array('department_add_direct', $perms)) $hasPower = true;
        }

        if (!$hasPower) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have approval rights.'], 403);
        }

        $department = Department::findOrFail($id);
        
        // 🛡️ SCOPE SECURITY
        if (!$context->is_god) {
            $cIds = $department->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $department->status = $request->status; 
        $department->save();

        $actionWord = $request->status === 'active' ? 'Approved' : 'Rejected';
        return response()->json(['status' => 'success', 'message' => "Department $actionWord Successfully!"]);
    }

}
