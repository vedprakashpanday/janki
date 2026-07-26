<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinePenalty;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;

class FinePenaltyApiController extends Controller
{
 public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        // 1. Determine User Permissions for Fine Management
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = $user && (in_array($user->email ?? '', $developerEmails) || class_basename($user) === 'SuperAdmin');
        
        $hasManagementPerms = false;
        if (!$isGodMode && $user && method_exists($user, 'getAllPermissions')) {
            $userPerms = $user->getAllPermissions()->pluck('name')->toArray();
            if (count(array_intersect(['fine_view', 'fine_edit', 'fine_delete', 'fine_add_direct', 'fine_add_request', 'fine_approve', 'fine_rej'], $userPerms)) > 0) {
                $hasManagementPerms = true;
            }
        }

        $query = FinePenalty::with(['employee.designation', 'employee.department', 'company']);

        // 🟢 2. APPLY SCOPING LOGIC
        if ($request->filled('personal_only') && $request->personal_only == 1) {
            // 🔥 PERSONAL PAGE: Strictly show only logged-in user's records regardless of permissions
            $query->whereHas('employee', function($q) use ($context) {
                $q->where('member_id', $context->profile_id);
            });
        } else {
            // 🏢 MANAGEMENT PAGE: Apply RBAC & Hierarchy Scoping
            if (!$isGodMode) {
                if ($context->is_employee) {
                    if ($hasManagementPerms) {
                        // Authorized Employee: Can see their company and branch
                        $query->where('company_id', $context->company_id);
                        if (!empty($context->branch_id)) {
                            $query->where(function($q) use ($context) {
                                $q->where('branch_id', $context->branch_id)
                                  ->orWhereNull('branch_id'); // Allow viewing HO level
                            });
                        }
                    } else {
                        // Normal Employee on Management page (Fallback security)
                        $query->whereHas('employee', function($q) use ($context) {
                            $q->where('member_id', $context->profile_id);
                        });
                    }
                } elseif ($context->is_director && $context->company_id) {
                    $query->where('company_id', $context->company_id);
                }
            }
        }

        // 🟢 3. APPLIED FILTERS LOGIC
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('designation_id')) $query->where('designation_id', $request->designation_id);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('start_date')) $query->whereDate('date', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('date', '<=', $request->end_date);
        
        // 🟢 4. LIVE SEARCH LOGIC
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('date', 'desc')->orderBy('id', 'desc')->get());
    }
// 1. Store Method Update (RBAC aur Notification Logic ke sath)
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        // Check God Mode / Master Admin
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = $user && (in_array($user->email ?? '', $developerEmails) || class_basename($user) === 'SuperAdmin');
        
        // 🔴 Main Logic: Status Check karna
        $hasDirect = $isGodMode || ($user && $user->can('fine_add_direct'));
        $finalStatus = $hasDirect ? 'Approved' : 'Pending';

       foreach ($request->employee_ids as $empId) {
            
            // 🟢 NAYA: Head Office ('HO_') check karke null karna
            $branchId = null;
            if (!empty($request->branch_id) && isset($request->branch_id[0]) && $request->branch_id[0] !== "" && $request->branch_id[0] !== "null") {
                $bId = (string) $request->branch_id[0];
                
                // Agar 'HO' se shuru ho raha hai, toh null hi rehne do (Head Office)
                if (!str_starts_with($bId, 'HO')) {
                    $branchId = $bId;
                }
            }

            $fine = FinePenalty::create([
                'user_type' => $request->user_type ?? 'Employee',
                'company_id' => is_array($request->company_id) ? $request->company_id[0] : $request->company_id,
                'branch_id' => $branchId,
                'department_id' => is_array($request->department_id) ? ($request->department_id[0] ?? null) : $request->department_id,
                'designation_id' => is_array($request->designation_id) ? ($request->designation_id[0] ?? null) : $request->designation_id,
                'employee_id' => $empId,
                'fine_rupees' => $request->fine_rupees,
                'fine_days' => $request->fine_days,
                'penalty_rupees' => $request->penalty_rupees,
                'penalty_days' => $request->penalty_days,
                'date' => $request->date,
                'treat_as' => $request->treat_as,
                'description' => $request->description,
                'proof_media_id' => $request->proof_media_ids,
                'status' => $finalStatus,
                'created_by' => $empId
            ]);

            // 🔴 Notification sirf tabhi bhejein jab status Approved ho
            if ($finalStatus === 'Approved') {
                $amountText = "";
                if ($request->fine_rupees) $amountText .= "₹" . $request->fine_rupees . " Fine ";
                if ($request->fine_days) $amountText .= $request->fine_days . " Day(s) Fine ";
                if ($request->penalty_rupees) $amountText .= "₹" . $request->penalty_rupees . " Penalty";

                $employee = \App\Models\Employee::find($empId);
                if ($employee) {
                    $employee->notify(new \App\Notifications\FinePenaltyAlert($fine->id, $amountText, $fine->user_type));
                }
            }
        }
        
        $message = $finalStatus === 'Pending' ? 'Fine/Penalty Requested (Pending Approval)' : 'Fine/Penalty Applied Successfully';
        return response()->json(['message' => $message]);
    }

    // 2. Approve Method Update (Approve hone par employee ko alert bhejna)
    public function approve($id)
    {
        $fine = FinePenalty::findOrFail($id);
        $fine->update(['status' => 'Approved']);
        
        // 🔴 Admin jab approve karega to notification bhejenge
        $amountText = "";
        if ($fine->fine_rupees) $amountText .= "₹" . $fine->fine_rupees . " Fine ";
        if ($fine->fine_days) $amountText .= $fine->fine_days . " Day(s) Fine ";
        if ($fine->penalty_rupees) $amountText .= "₹" . $fine->penalty_rupees . " Penalty";

        $employee = \App\Models\Employee::find($fine->employee_id);
        if ($employee) {
            $employee->notify(new \App\Notifications\FinePenaltyAlert($fine->id, $amountText, $fine->user_type ?? 'Employee'));
        }

        return response()->json(['message' => 'Fine/Penalty Approved & Notification Sent!']);
    }

    // 2. Department Filter Logic (HO Filter Handling)
    public function getFilteredDepartments(Request $request)
    {
        $query = Department::where('department_name', 'not like', '%Associate%');
        $companyId = (string) $request->company_id;

        $query->where(function($q) use ($companyId) {
            $q->whereJsonContains('company_ids', $companyId)
              ->orWhereJsonContains('company_ids', 'all');
        });

        $branchIds = $request->branch_ids;
        
        // Null, empty array, or "null" string check (Head Office handling)
        if (!empty($branchIds) && is_array($branchIds) && $branchIds[0] !== "" && $branchIds[0] !== "null") {
            $query->where(function($q) use ($branchIds) {
                foreach ($branchIds as $bId) {
                    if(!empty($bId) && $bId !== "null") {
                        $q->orWhereJsonContains('branch_ids', (string) $bId);
                    }
                }
            });
        } else {
            // Agar Head Office (null) hai, to wo departments laao jinka branch_id null ya 'all' hai
            $query->where(function($q) {
                $q->whereNull('branch_ids')
                  ->orWhereJsonContains('branch_ids', null)
                  ->orWhereJsonContains('branch_ids', 'all');
            });
        }

        return response()->json($query->get());
    }

    
    public function bulkDelete(Request $request)
    {
        FinePenalty::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }


   public function getFilteredDesignations(Request $request)
    {
        $deptIds = $request->department_ids;
        // Agar array empty hai ya null aaya hai, to turant blank array return kardo
        if (empty($deptIds) || !is_array($deptIds)) {
            return response()->json([]);
        }
        
        $query = Designation::whereIn('department_id', $deptIds);
        return response()->json($query->get());
    }

  public function getFilteredEmployees(Request $request)
    {
        $query = Employee::query();

        // 1. Text Search Logic (Live Search from Select2)
        if ($request->has('q') && strlen($request->q) > 0) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%");
            });
        }

        // 2. Dependency Filters
        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        }
        
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
            $query->where(function ($q) use ($normalBranchIds, $hoCompanyIds) {
                if (count($normalBranchIds) > 0) $q->whereIn('branch_id', $normalBranchIds);
                if (count($hoCompanyIds) > 0) {
                    $q->orWhere(function ($subQ) use ($hoCompanyIds) {
                        $subQ->whereIn('company_id', $hoCompanyIds)->whereNull('branch_id');
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

        // Fetch Data safely without touching EmployeeController
        $employees = $query->select('id', 'full_name', 'member_id')->limit(20)->get();
        
        return response()->json(['status' => 'success', 'data' => $employees]);
    }
   

    public function reject($id)
    {
        $fine = FinePenalty::findOrFail($id);
        $fine->update(['status' => 'Rejected']);
        return response()->json(['message' => 'Fine/Penalty Rejected']);
    }

    public function updateRemark(Request $request, $id)
    {
        $request->validate(['description' => 'required']);
        $fine = FinePenalty::findOrFail($id);
        
        // Purana description aur naya remark concatenate kar sakte hain ya overwrite.
        $newDescription = $fine->description . "<br><b>Remark:</b> " . $request->description;
        $fine->update(['description' => $newDescription]);
        
        return response()->json(['message' => 'Remark Updated']);
    }

    public function printPreview(Request $request, $id)
    {
        $fine = FinePenalty::with(['employee.designation', 'employee.department', 'company'])->findOrFail($id);
        $company = $fine->company;
        
        // 1. Proof Media Logic & Toggle Flag
        $withProof = $request->query('with_proof', 0); // URL se parameter read karega, default 0 (No images)
        $hasProof = false;
        $proofMediaList = [];

        if (!empty($fine->proof_media_id)) {
            $hasProof = true;
            $mediaIds = array_filter(explode(',', $fine->proof_media_id));
            $proofMediaList = \DB::table('media')->whereIn('id', $mediaIds)->get();
        }

        // 2. Authorized Signatory Logic (Based on created_by)
        $signatoryName = "Authorized Signatory";
        
        // Check in Users Table (For Admin/HR)
        $user = \DB::table('users')->where('id', $fine->created_by)->first();
        if ($user && strtolower($user->email) === 'admin@jankivilla.com') {
            $signatoryName = "HR Management";
        } else {
            // Check in Super Admins Table
            $superAdmin = \DB::table('super_admins')->where('id', $fine->created_by)->first();
            if ($superAdmin) {
                $signatoryName = $superAdmin->full_name . " (" . $superAdmin->ceo_id . ")";
            } else {
                // Check in Directors Table
                $director = \DB::table('directors')->where('id', $fine->created_by)->first();
                if ($director) {
                    $signatoryName = $director->full_name . " (" . $director->director_id . ")";
                }
            }
        }

        // 3. Salary Deduction & Total Calculation Logic
        $salaryPerDay = 0;
        $fineDaysAmount = 0;
        $penaltyDaysAmount = 0;

        // Sirf tab calculate karein jab user Employee ho aur payable_salary > 0 ho
        if (strtolower($fine->user_type ?? 'employee') === 'employee' && $fine->employee && $fine->employee->payable_salary) {
            $salaryPerDay = $fine->employee->payable_salary / 30;
            
            if ($fine->fine_days) {
                $fineDaysAmount = $fine->fine_days * $salaryPerDay;
            }
            if ($fine->penalty_days) {
                $penaltyDaysAmount = $fine->penalty_days * $salaryPerDay;
            }
        }

        // Grand Totals
        $totalAmount = ($fine->fine_rupees ?? 0) + ($fine->penalty_rupees ?? 0) + $fineDaysAmount + $penaltyDaysAmount;
        $totalDays = ($fine->fine_days ?? 0) + ($fine->penalty_days ?? 0);

        // 4. Manual Designation Fetch Logic (adm_regist -> designations)
        $designationName = 'N/A';
        if ($fine->employee && $fine->employee->designation_id) {
            $designationRecord = \Illuminate\Support\Facades\DB::table('designations')
                ->where('id', $fine->employee->designation_id)
                ->first();
                
            if ($designationRecord) {
                $designationName = $designationRecord->designation_name;
            }
        }

       // Return view me $designationName ko pass karein
        return view('admin.fine_penalties.print', compact(
            'fine', 'company', 'proofMediaList', 'hasProof', 'withProof', 'signatoryName',
            'fineDaysAmount', 'penaltyDaysAmount', 'totalAmount', 'totalDays', 'designationName'
        ));
    }

    public function show($id)
    {
        $fine = FinePenalty::with(['employee.department', 'employee.designation', 'employee.branch', 'company'])->findOrFail($id);
        
        // 1. Render Header Component HTML inside Controller
        $headerHtml = view('components.print-header', [
            'company' => $fine->company,
            'branch' => $fine->employee->branch ?? null
        ])->render();
        
        $fine->header_html = $headerHtml;

        // 2. Fetch Multiple Proof Images if available
        if (!empty($fine->proof_media_id)) {
            $mediaIds = array_filter(explode(',', $fine->proof_media_id));
            $fine->proof_media_list = \DB::table('media')->whereIn('id', $mediaIds)->get();
        } else {
            $fine->proof_media_list = [];
        }

        return response()->json($fine);
    }

    public function update(Request $request, $id)
    {
        $fine = FinePenalty::findOrFail($id);
        
        // Update only relevant fields (Company, Employee wagerah update nahi karne denge taaki record safe rahe)
        $fine->update([
            'fine_rupees' => $request->fine_rupees,
            'fine_days' => $request->fine_days,
            'penalty_rupees' => $request->penalty_rupees,
            'penalty_days' => $request->penalty_days,
            'date' => $request->date,
            'treat_as' => $request->treat_as,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Record Updated Successfully']);
    }

    public function myPenaltiesPage()
    {
        // Ye employee (ya member) ke liye dedicated page return karega
        return view('employee.my_penalties'); 
    }

    public function printAllPreview(Request $request)
    {
        $query = FinePenalty::with(['employee.designation', 'employee.department', 'company']);
        
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('designation_id')) $query->where('designation_id', $request->designation_id);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('start_date')) $query->whereDate('date', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('date', '<=', $request->end_date);

        $fines = $query->orderBy('date', 'desc')->get();
        
        // Header component ke liye company laana
        $companyId = $request->filled('company_id') ? $request->company_id : 1;
        $company = \App\Models\Company::find($companyId);
        
        return view('admin.fine_penalties.print_all', compact('fines', 'company'));
    }


}