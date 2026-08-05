<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Member;

class TaskDependencyController extends Controller
{
    public function getCompanies(Request $request)
    {
        $search = $request->get('search');
        $query = Company::where('status', 'active');

        if (!empty($search)) {
            $query->where('company_name', 'LIKE', "%{$search}%");
        }
        return response()->json(['status' => 'success', 'data' => $query->select('id', 'company_name')->get()]);
    }

    public function getBranches(Request $request)
    {
        $search = $request->get('search');
        $companyIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
        
        $query = Branch::where('branch_status', 'active');

        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }
        if (!empty($search)) {
            $query->where('branch_name', 'LIKE', "%{$search}%");
        }

        return response()->json(['status' => 'success', 'data' => $query->select('id', 'branch_name', 'company_id')->get()]);
    }

   public function getDepartments(Request $request)
{
    $search = $request->get('search');
    $companyIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
    $branchIds = $request->filled('branch_ids') ? explode(',', $request->branch_ids) : [];
    $assigneeType = $request->get('assignee_type', 'App\Models\Employee');

    if (empty($branchIds)) {
        return response()->json(['status' => 'success', 'data' => []]);
    }

    $query = Department::where('status', 'active');

    if (!empty($search)) {
        $query->where('department_name', 'LIKE', "%{$search}%");
    }

    // 🔥 DEPT ISOLATION LOGIC 🔥
    if ($assigneeType === 'App\Models\Member') {
        // Associate page: Only show Associate / Field related departments
        $query->where(function($q) {
            $q->where('department_name', 'LIKE', '%Associate%')
              ->orWhere('department_name', 'LIKE', '%Member%')
              ->orWhere('department_name', 'LIKE', '%Agent%');
        });
    } else {
        // Staff page: Skip Associate departments
        $query->where('department_name', 'NOT LIKE', '%Associate%')
              ->where('department_name', 'NOT LIKE', '%Member%');
    }

    // Handling NULL and Company/Branch matching
    if (!empty($companyIds)) {
        $query->where(function($q) use ($companyIds) {
            $q->whereNull('company_ids')
              ->orWhereJsonContains('company_ids', 'all');
            foreach ($companyIds as $cId) {
                $q->orWhereJsonContains('company_ids', (string)$cId)
                  ->orWhereJsonContains('company_ids', (int)$cId);
            }
        });
    }

    if (!empty($branchIds)) {
        $normalBIds = [];
        foreach ($branchIds as $bId) {
            if (!str_starts_with($bId, 'HO_')) $normalBIds[] = $bId;
        }
        $query->where(function($q) use ($normalBIds) {
            $q->whereNull('branch_ids')
              ->orWhereJsonContains('branch_ids', 'all');
            if (count($normalBIds) > 0) {
                foreach ($normalBIds as $bId) {
                    $q->orWhereJsonContains('branch_ids', (string)$bId)
                      ->orWhereJsonContains('branch_ids', (int)$bId);
                }
            }
        });
    }

  
// Purana return statement hata do aur ye use karo:

        $departments = $query->selectRaw('GROUP_CONCAT(id) as grouped_id, department_name')
                             ->groupBy('department_name')
                             ->get()
                             ->map(function($item) {
                                 // Yahan hum string (e.g. "9,15") ko manually id me daal rahe hain 
                                 // taaki Laravel use integer me na badle
                                 return [
                                     'id' => $item->grouped_id, 
                                     'department_name' => $item->department_name
                                 ];
                             });

        return response()->json([
            'status' => 'success', 
            'data' => $departments
        ]);
}

    public function getDesignations(Request $request)
    {
        $search = $request->get('search');
        $deptIds = $request->filled('department_ids') ? explode(',', $request->department_ids) : [];
        
        // 🔒 SECURITY: Agar Department select nahi hai, toh Designation kabhi load nahi hoga
        if (empty($deptIds)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }
        
        $query = Designation::where('status', 'active')->whereIn('department_id', $deptIds);
        
        if (!empty($search)) {
            $query->where('designation_name', 'LIKE', "%{$search}%");
        }
        return response()->json(['status' => 'success', 'data' => $query->select('id', 'designation_name')->get()]);
    }

    
    public function getEmployees(Request $request)
    {
        $query = Employee::where('emp_status', 'active');
        $this->applyUserFilters($query, $request);
        return response()->json(['status' => 'success', 'data' => $query->select('id', 'full_name', 'member_id')->get()]);
    }

    public function getMembers(Request $request)
    {
        $query = Member::where('status', 'active');
        $this->applyUserFilters($query, $request);
        return response()->json(['status' => 'success', 'data' => $query->select('id', 'member_name as full_name', 'member_id')->get()]); // member_name aliased as full_name for uniform JS
    }

    // Common Filter Logic for both Employees and Members
    private function applyUserFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $nameCol = $q->getModel() instanceof Member ? 'member_name' : 'full_name';
                $q->where($nameCol, 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        }
        
        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $normalBIds = []; $hoCIds = [];
            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) $hoCIds[] = str_replace('HO_', '', $bId);
                else $normalBIds[] = $bId;
            }
            $query->where(function ($sq) use ($normalBIds, $hoCIds) {
                if (count($normalBIds) > 0) $sq->whereIn('branch_id', $normalBIds);
                if (count($hoCIds) > 0) {
                    $sq->orWhere(function ($ssq) use ($hoCIds) {
                        $ssq->whereIn('company_id', $hoCIds)->whereNull('branch_id');
                    });
                }
            });
        }

        if ($request->filled('department_ids')) {
            $query->whereIn('department_id', explode(',', $request->department_ids));
        }
        if ($request->filled('designation_ids')) {
            $query->whereIn('designation_id', explode(',', $request->designation_ids));
        }
    }
}