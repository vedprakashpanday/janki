<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class IncentiveApiController extends Controller
{
    // ========================================================================
    // 1. COMPANY SEARCH (3 Letters)
    // ========================================================================
    public function searchCompanies(Request $request)
    {
        $q = $request->q;
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);

        $context = $this->getGlobalContext();
        
        $query = Company::where('status', 'active')
            ->where(function($sq) use ($q) {
                $sq->where('company_name', 'LIKE', "%{$q}%")
                   ->orWhere('company_code', 'LIKE', "%{$q}%");
            });

        // 🛡️ LOCKING LOGIC: Agar admin/master nahi hai to sirf apni company
        if (!$context->is_god && !$context->is_director) {
            // Check for Master HO Bypass
            $isMasterHO = false;
            if ($context->is_employee && empty($context->branch_id) && !empty($context->company_id)) {
                $comp = Company::find($context->company_id);
                if ($comp && empty($comp->parent_id)) $isMasterHO = true;
            }

            if (!$isMasterHO && $context->company_id) {
                $query->where('id', $context->company_id);
            }
        }

        $companies = $query->limit(20)->get(['id', 'company_name', 'company_code']);
        return response()->json(['status' => 'success', 'data' => $companies]);
    }

    // ========================================================================
    // 2. BRANCH SEARCH (3 Letters, Skip 'associates', Inject HO)
    // ========================================================================
    public function searchBranches(Request $request)
    {
        $q = $request->q;
        $companyIds = $request->company_ids ?? []; // Multi-select array

        if (strlen($q) < 3 || empty($companyIds)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $context = $this->getGlobalContext();
        
        $query = Branch::where('branch_status', 'active')
            ->whereIn('company_id', $companyIds)
            ->where('branch_name', 'NOT LIKE', "%associate%") // Skipping associates
            ->where('branch_name', 'LIKE', "%{$q}%");

        // 🛡️ LOCKING LOGIC: Agar employee branch me locked hai
        if (!$context->is_god && !$context->is_director && $context->is_employee) {
            if ($context->branch_id) {
                $query->where('id', $context->branch_id); // Branch locked
            }
        }

        $branches = $query->limit(20)->get(['id', 'branch_name', 'company_id']);
        
        // 🔥 HEAD OFFICE INJECTION: 
        // Agar context lock nahi hai branch par, to dynamically "Head Office" suggest karenge
        $results = $branches->toArray();
        if (!$context->branch_id) {
            $companies = Company::whereIn('id', $companyIds)->get();
            foreach ($companies as $comp) {
                $hoName = "Head Office (" . $comp->company_name . ")";
                // Agar 3 letters match karte hain HO ke naam se ya user ne 'head' type kiya
                if (stripos($hoName, $q) !== false || stripos('head office', $q) !== false || stripos('ho', $q) !== false) {
                    // HO ke liye hum 'HO_companyId' bhejte hain taaki departments handle kar sake
                    array_unshift($results, [
                        'id' => 'HO_' . $comp->id, 
                        'branch_name' => $hoName,
                        'company_id' => $comp->id
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success', 'data' => $results]);
    }

    // ========================================================================
    // 3. DEPARTMENT SEARCH (JSON Formats handling)
    // ========================================================================
    public function searchDepartments(Request $request)
    {
        $q = $request->q;
        $companyIds = $request->company_ids ?? []; 
        $branchIds = $request->branch_ids ?? []; 

        if (strlen($q) < 3 || empty($companyIds)) return response()->json(['status' => 'success', 'data' => []]);

        $query = Department::where('status', 'active')
            ->where('department_name', 'LIKE', "%{$q}%")
            ->where('department_name', 'NOT LIKE', "%associate%"); // Ensure no associates

        // 1. Company JSON logic
        $query->where(function ($qBuilder) use ($companyIds) {
            $qBuilder->whereNull('company_ids')
                     ->orWhereJsonContains('company_ids', 'all');
            foreach ($companyIds as $cId) {
                $qBuilder->orWhereJsonContains('company_ids', (string)$cId)
                         ->orWhereJsonContains('company_ids', (int)$cId);
            }
        });

        // 2. Branch JSON logic (Handling HO vs Normal Branch)
        if (!empty($branchIds)) {
            $normalBranchIds = [];
            $hoCompanyIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_') || $bId === 'null' || $bId === '') {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId);
                } else {
                    $normalBranchIds[] = $bId;
                }
            }

            $query->where(function ($subQ) use ($normalBranchIds, $hoCompanyIds) {
                // If normal branches selected
                foreach ($normalBranchIds as $nId) {
                    $subQ->orWhereJsonContains('branch_ids', 'all')
                         ->orWhereJsonContains('branch_ids', (string)$nId)
                         ->orWhereJsonContains('branch_ids', (int)$nId);
                }
                
                // If Head Office is selected (Null branch_ids)
                if (count($hoCompanyIds) > 0) {
                    $subQ->orWhere(function ($hoQ) {
                        $hoQ->whereNull('branch_ids')
                            ->orWhereJsonContains('branch_ids', null)
                            ->orWhereJsonContains('branch_ids', 'all');
                    });
                }
            });
        }

        $departments = $query->orderBy('department_name', 'asc')->limit(20)->get(['id', 'department_name']);
        return response()->json(['status' => 'success', 'data' => $departments]);
    }

    // ========================================================================
    // 4. DESIGNATION SEARCH
    // ========================================================================
    public function searchDesignations(Request $request)
    {
        $q = $request->q;
        $departmentIds = $request->department_ids ?? [];

        if (strlen($q) < 3 || empty($departmentIds)) return response()->json(['status' => 'success', 'data' => []]);

        $query = Designation::where('status', 'active')
            ->whereIn('department_id', $departmentIds)
            ->where('designation_name', 'LIKE', "%{$q}%");

        $designations = $query->limit(20)->get(['id', 'designation_name', 'designation_code']);
        return response()->json(['status' => 'success', 'data' => $designations]);
    }

    // ========================================================================
    // 5. EMPLOYEE SEARCH (From adm_regist / employees table)
    // ========================================================================
    public function searchEmployees(Request $request)
    {
        $q = $request->q;
        $designationIds = $request->designation_ids ?? [];
        $branchIds = $request->branch_ids ?? [];
        
        if (strlen($q) < 3 || empty($designationIds)) return response()->json(['status' => 'success', 'data' => []]);

        $query = Employee::where('emp_status', 'active')
            ->whereIn('designation_id', $designationIds)
            ->where(function($sq) use ($q) {
                $sq->where('full_name', 'LIKE', "%{$q}%")
                   ->orWhere('member_id', 'LIKE', "%{$q}%");
            });

        // Filter by branch logic if needed
        $normalBranchIds = [];
        $hasHo = false;
        foreach ($branchIds as $bId) {
            if (str_starts_with($bId, 'HO_') || $bId === 'null') {
                $hasHo = true;
            } else {
                $normalBranchIds[] = $bId;
            }
        }
        
        if (!empty($branchIds)) {
            $query->where(function($qBuilder) use ($normalBranchIds, $hasHo) {
                if(count($normalBranchIds) > 0) {
                    $qBuilder->orWhereIn('branch_id', $normalBranchIds);
                }
                if ($hasHo) {
                    $qBuilder->orWhereNull('branch_id');
                }
            });
        }

        // Return id (primary key or member_id based on your logic), full_name, member_id
        $employees = $query->limit(20)->get(['id', 'member_id', 'full_name']);
        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    // ========================================================================
    // 6. DATATABLES & CARDS INDEX (Read)
    // ========================================================================
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        
        // Eager load relations for UI
        $query = \App\Models\Incentive::with([
            'company:id,company_name,company_code', 
            'branch:id,branch_name', 
            'department:id,department_name', 
            'designation:id,designation_name', 
            'employee:id,member_id,full_name',
            'type:id,name'
        ]);

        // 🛡️ ZERO-TRUST SCOPING
        if (!$context->is_god) {
            $userPerms = $context->permissions ?? [];

            // Director ko uski puri company ka access
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } else {
                // Employee ko sirf uski location ya uske banaye records dikhenge
                $query->where('created_by', auth()->id());
                if ($context->is_employee) {
                    $query->where('company_id', $context->company_id)
                          ->where('branch_id', $context->branch_id);
                }
            }
        }

        // 🔥 TIME SCOPE FILTER (Current Date vs All Time)
        if ($request->input('time_scope') !== 'all_time') {
            $today = \Carbon\Carbon::today()->toDateString();
            $query->whereDate('created_at', $today);
        }

        $totalData = \App\Models\Incentive::count();

        // 🔍 GLOBAL SEARCH
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%");
            })->orWhere('passbook_no', 'LIKE', "%{$search}%")
              ->orWhere('dv_no', 'LIKE', "%{$search}%");
        }

        $totalFiltered = $query->count();

        // 📄 PAGINATION (Desktop DataTables & Mobile Load More)
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $incentives = $query->latest()->get();

        // 🔑 DYNAMIC PERMISSIONS FOR UI RENDERING
        $user = auth()->user();
        $permsList = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];
        $isGod = $context->is_god;

        // Determine prefix based on time_scope to check correct permissions
        $pfx = ($request->input('time_scope') === 'all_time') ? 'incentive_dir_' : 'incentive_';

        $permissions = [
            'can_add_direct'  => $isGod || in_array($pfx . 'add_direct', $permsList),
            'can_add_request' => $isGod || in_array($pfx . 'add_request', $permsList),
            'can_edit'        => $isGod || in_array($pfx . 'edit', $permsList),
            'can_delete'      => $isGod || in_array($pfx . 'delete', $permsList), // Permanent delete
            'can_print'       => $isGod || in_array($pfx . 'print', $permsList),
            'can_export'      => $isGod || in_array($pfx . 'export', $permsList),
            'can_approve'     => $isGod || in_array($pfx . 'appr', $permsList),
            'can_reject'      => $isGod || in_array($pfx . 'rej', $permsList),
        ];

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data"            => $incentives,
            "permissions"     => $permissions
        ]);
    }

  // ========================================================================
    // 7. STORE METHOD (UPDATED FOR MEMBER_ID)
    // ========================================================================
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        $permsList = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];
        $pfx = ($request->input('time_scope') === 'all_time') ? 'incentive_dir_' : 'incentive_';
        
        $hasDirect = $context->is_god || in_array($pfx . 'add_direct', $permsList);
        $hasRequest = in_array($pfx . 'add_request', $permsList);

        if (!$hasDirect && !$hasRequest && !$context->is_god) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        DB::beginTransaction();
        try {
            $empIds = $request->emp_ids ?? []; // Ab yahan member_id (e.g. ABDPL-A/001) aayega
            if (empty($empIds)) return response()->json(['status' => 'error', 'message' => 'Please select employees.'], 400);

            $netAmount = (float)($request->net_amount ?? 0);
            $value = (float)($request->value ?? 0);
            $calcType = $request->calc_type; 
            $distType = $request->dist_type; 
            
            $baseComputed = ($calcType === 'percentage') ? ($netAmount * $value / 100) : $value;
            $finalAmountPerEmp = ($distType === 'all' && count($empIds) > 0) ? ($baseComputed / count($empIds)) : $baseComputed;
            $status = $hasDirect ? 'active' : 'pending';

            foreach ($empIds as $memberId) {
                // 🔥 FIX: Find profile using member_id instead of id
                $empProfile = \App\Models\Employee::where('member_id', $memberId)->first();
                
                \App\Models\Incentive::create([
                    'company_id'        => $empProfile->company_id ?? null,
                    'branch_id'         => $empProfile->branch_id ?? null,
                    'department_id'     => $empProfile->department_id ?? null,
                    'designation_id'    => $empProfile->designation_id ?? null,
                    'emp_id'            => $memberId, // Saving string member_id
                    'incentive_type_id' => $request->incentive_type_id,
                    'passbook_no'       => $request->passbook_no,
                    'net_amount'        => $netAmount,
                    'calc_type'         => $calcType,
                    'dist_type'         => $distType,
                    'value'             => $value,
                    'calculated_amount' => $finalAmountPerEmp,
                    'left'              => $finalAmountPerEmp,
                    'total_left'        => $finalAmountPerEmp,
                    'incentive_status'  => $status,
                    'created_by'        => auth()->id(),
                ]);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'message' => 'Incentives generated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ========================================================================
    // NAYA: SHOW METHOD (FOR VIEW MODAL WITH HISTORY)
    // ========================================================================
    public function show($id)
    {
        $incentive = \App\Models\Incentive::with(['company', 'branch', 'department', 'designation', 'employee', 'type'])->findOrFail($id);
        
        // History: Is member ki is record ke created_at ya usse pehle ki saari history
        $history = \App\Models\Incentive::with('type')
            ->where('emp_id', $incentive->emp_id)
            ->where('created_at', '<=', $incentive->created_at)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $incentive, 'history' => $history]);
    }

    // ========================================================================
    // 8. APPROVE & REJECT ACTIONS (Maker-Checker)
    // ========================================================================
    public function approve(Request $request, $id)
    {
        $incentive = \App\Models\Incentive::findOrFail($id);
        $incentive->update(['incentive_status' => 'active', 'updated_by' => auth()->id()]);
        return response()->json(['status' => 'success', 'message' => 'Incentive Approved!']);
    }

    public function reject(Request $request, $id)
    {
        $incentive = \App\Models\Incentive::findOrFail($id);
        $incentive->update(['incentive_status' => 'rejected', 'updated_by' => auth()->id()]);
        return response()->json(['status' => 'success', 'message' => 'Incentive Rejected!']);
    }

    // ========================================================================
    // 9. SOFT DELETE (Action Column Temporary Delete)
    // ========================================================================
    public function destroy($id)
    {
        // Sabko by default allow karna hai soft delete as per your requirement
        $incentive = \App\Models\Incentive::findOrFail($id);
        $incentive->delete(); // Soft delete executed
        
        return response()->json(['status' => 'success', 'message' => 'Incentive moved to trash temporarily.']);
    }

    // ========================================================================
    // 10. BULK PERMANENT DELETE (Select All Checkboxes)
    // ========================================================================
    public function bulkDeletePermanent(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        $permsList = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];
        $pfx = ($request->input('time_scope') === 'all_time') ? 'incentive_dir_' : 'incentive_';

        if (!$context->is_god && !in_array($pfx . 'delete', $permsList)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to permanently delete!'], 403);
        }

        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No incentives selected!'], 400);
        }

        DB::beginTransaction();
        try {
            // Force delete removes it permanently bypassing soft deletes
            \App\Models\Incentive::whereIn('id', $ids)->forceDelete();
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Selected records have been permanently deleted!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete records permanently.'], 500);
        }
    }

 // ========================================================================
    // 11. PRINT PREVIEW PAGE
    // ========================================================================
    public function printPreview(Request $request)
    {
        // 🔥 FIX: URL se token nikal kar manual auth karenge
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) {
                auth()->login($accessToken->tokenable);
            }
        }

        $context = $this->getGlobalContext();
        
        // Safety net agar token invalid ho
        if (!$context) {
            return response("Unauthorized Access! Please login again.", 401);
        }
        
        $query = \App\Models\Incentive::with([
            'company', 'branch', 'department', 'designation', 'employee', 'type'
        ]);

        // Same Zero-Trust Scoping as Index
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } else {
                $query->where('created_by', auth()->id());
                if ($context->is_employee) {
                    $query->where('company_id', $context->company_id)
                          ->where('branch_id', $context->branch_id);
                }
            }
        }

        // Time scope filter
        if ($request->input('time_scope') !== 'all_time') {
            $today = \Carbon\Carbon::today()->toDateString();
            $query->whereDate('created_at', $today);
        }

        $incentives = $query->latest()->get();

        // Print Header ke liye Company aur Branch object fetch karna
        $companyId = $context->is_god ? 1 : ($context->company_id ?? 1);
        $branchId = $context->is_god ? null : ($context->branch_id ?? null);
        
        $company = \App\Models\Company::find($companyId);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        return view('admin.incentives.print', compact('incentives', 'company', 'branch'));
    }

   // ========================================================================
    // NAYA: PRINT INDIVIDUAL RECEIPT METHOD
    // ========================================================================
    public function printReceipt(Request $request, $id)
    {
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) auth()->login($accessToken->tokenable);
        }

        $context = $this->getGlobalContext();
        if (!$context) return response("Unauthorized!", 401);

        $incentive = \App\Models\Incentive::with(['company', 'branch', 'department', 'designation', 'employee', 'type'])->findOrFail($id);
        
        $history = \App\Models\Incentive::with('type')
            ->where('emp_id', $incentive->emp_id)
            ->where('created_at', '<=', $incentive->created_at)
            ->orderBy('created_at', 'asc')
            ->get();

        $companyId = $context->is_god ? ($incentive->company_id ?? 1) : ($context->company_id ?? 1);
        $branchId = $context->is_god ? ($incentive->branch_id ?? null) : ($context->branch_id ?? null);
        
        $company = \App\Models\Company::find($companyId);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        // 🔥 FIX: Fetching Dynamic Authorized Signatory Name
        $signatoryName = $this->getAuthorizedSignatoryName($incentive->created_by);

        return view('admin.incentives.receipt', compact('incentive', 'history', 'company', 'branch', 'signatoryName'));
    }

    // ========================================================================
    // HELPER: GET DYNAMIC SIGNATORY NAME BASED ON CREATED_BY
    // ========================================================================
    private function getAuthorizedSignatoryName($creatorId)
    {
        if (!$creatorId) return 'Authorized Signatory';

        // 1. Check Master Admin / Users Table
        $user = DB::table('users')->where('id', $creatorId)->first();
        if ($user && strtolower($user->email) === 'admin@jankivilla.com') {
            return 'HR MANAGEMENT';
        }

        // 2. Check CEO / Super Admins
        $sa = DB::table('super_admins')->where('id', $creatorId)->first();
        if ($sa && isset($sa->ceo_id)) {
            return ($sa->full_name ?? 'CEO') . ' (' . $sa->ceo_id . ')';
        }

        // 3. Check Directors
        $dir = DB::table('directors')->where('id', $creatorId)->first();
        if ($dir && isset($dir->director_id)) {
            return ($dir->full_name ?? 'Director') . ' (' . $dir->director_id . ')';
        }

        // 4. Check Employees / adm_regist
        $emp = DB::table('adm_regist')->where('id', $creatorId)->first();
        if (!$emp) {
            // Fallback agar employees table alag ho
            $emp = DB::table('employees')->where('id', $creatorId)->first(); 
        }
        
        if ($emp && isset($emp->member_id)) {
            return ($emp->full_name ?? 'Employee') . ' (' . $emp->member_id . ')';
        }

        // Fallback default
        return $user ? ($user->name ?? 'Authorized Signatory') : 'Authorized Signatory';
    }

}