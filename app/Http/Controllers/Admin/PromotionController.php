<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Member;

// 🔥 NAYE IMPORTS JO NOTIFICATION KE LIYE ZAROORI HAIN 🔥
use App\Models\PromotionTemplate;
use App\Models\Designation;
use App\Models\Company;
use App\Notifications\GreetingNotification;
use App\Notifications\PromotionNotification;

class PromotionController extends Controller
{
    // Blade view return karega
    public function index()
    {
        return view('admin.promotions.index');
    }

    // Smart Search API for Auto-Suggest
    public function searchStaff(Request $request)
    {
        $type = $request->staff_type; // 'employee' or 'member'
        $companyId = $request->company_id;
        $branchId = $request->branch_id;
        $departmentId = $request->department_id;
        $designationId = $request->designation_id;
        $search = $request->search; // User jo name type karega

        if ($type === 'employee') {
            $query = Employee::where('emp_status', 'active');
        } else {
            $query = Member::where('status', 'active');
        }

        // Apply strict filters
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId !== null && $branchId !== '') {
            // Check for Head Office logic
            if ($branchId === 'HO' || str_starts_with($branchId, 'HO_')) {
                $query->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', '');
                });
            } else {
                $query->where('branch_id', $branchId);
            }
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($designationId) {
            $query->where('designation_id', $designationId);
        }

        // Apply name/ID search using LIKE
        if ($search) {
            $query->where(function($q) use ($search, $type) {
                if ($type === 'employee') {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                      ->orWhere('member_id', 'LIKE', "%{$search}%");
                } else {
                    $q->where('member_name', 'LIKE', "%{$search}%")
                      ->orWhere('member_id', 'LIKE', "%{$search}%");
                }
            });
        }

        // Sirf zaroori fields return karenge load kam karne ke liye
        $staff = $query->limit(20)->get()->map(function($user) use ($type) {
            return [
                'id' => $user->id,
                'member_id' => $user->member_id,
                'name' => $type === 'employee' ? $user->full_name : $user->member_name,
                'salary' => $user->current_salary ?? 0
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $staff
        ]);
    }


    // ==========================================
    // CORE PROMOTION LOGIC (Phase 4 + Phase 5 Notification)
    // ==========================================
    public function submitPromotion(Request $request)
    {
       $request->validate([
            'staff_type' => 'required|in:employee,member',
            'company_id' => 'required|integer',
            'department_id' => 'required|integer',
            'designation_id' => 'required|integer',
            'staff_ids' => 'required|array',
            'new_salary' => 'required|numeric',
            'effective_date' => 'required|date' // 🔥 NAYA VALIDATION
        ]);

        $type = $request->staff_type;
        $branchId = $request->branch_id; // Ye null bhi ho sakta hai (HO ke liye)
        $companyId = $request->company_id;
        $departmentId = $request->department_id;
        $designationId = $request->designation_id;
        $newSalary = $request->new_salary;
        $staffIds = $request->staff_ids;
        $effectiveDateDb = $request->effective_date; // Database ke liye format: YYYY-MM-DD
        $effectiveDateFormatted = date('d M, Y', strtotime($effectiveDateDb)); // Template ke liye format: 01 Jul, 2026

        // 2. Database Transaction Shuru
        \Illuminate\Support\Facades\DB::beginTransaction();
        
        try {
            // 🔥 NAYA LOGIC: Template aur names fetch karna loop se pehle
            $template = PromotionTemplate::where('type', $type)->first();
            $newDesigName = Designation::find($designationId)->designation_name ?? 'N/A';
            $newCompName = Company::find($companyId)->company_name ?? 'N/A';
            $effectiveDate = date('d M, Y');

            if ($type === 'employee') {
                foreach ($staffIds as $empId) {
                    $employee = Employee::findOrFail($empId);

                    // Template ke liye purana data save karna
                    $oldDesigName = $employee->designation ? $employee->designation->designation_name : 'N/A';
                    $oldSalaryVal = $employee->current_salary ?? 0;
                    $userName = $employee->full_name;

                    // A. Main Table Update
                    $employee->company_id = $companyId;
                    $employee->branch_id = $branchId;
                    $employee->department_id = $departmentId;
                    $employee->designation_id = $designationId;
                    $employee->current_salary = $newSalary;
                    $employee->payable_salary = $newSalary;
                    $employee->save();

                   // 1. Purane Service Record ko close karna (date_of_leaving update karna)
                    $lastService = \App\Models\ServiceRecord::where('user_id', $employee->id)
                                    ->orderBy('id', 'desc')
                                    ->first();
                                    
                    if ($lastService) {
                        // Nayi joining date se 1 din pehle ki date nikal rahe hain (HR Standard)
                        // Agar strictly same date chahiye toh: $lastService->date_of_leaving = $effectiveDateDb; likh dena.
                        $lastService->date_of_leaving = \Carbon\Carbon::parse($effectiveDateDb)->subDay()->format('Y-m-d');
                        $lastService->save();
                    }

                    // 2. Naya Service Record Insert karna
                    \App\Models\ServiceRecord::create([
                        'user_id' => $employee->id,
                        'member_id' => $employee->member_id,
                        'service_id' => $employee->service_id,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'designation_id' => $designationId,
                        'current_salary' => $newSalary,
                        'joining_date' => $effectiveDateDb, 
                        'promotion_date' => $effectiveDateDb, // 🔥 Yahan promotion_date bhi add ho gaya
                        'role' => $employee->role,
                        'status' => $employee->emp_status,
                    ]);

                    // 🔥 C. Parse & Dispatch Notification (WITH FALLBACK)
                    $title = $template->subject ?? 'Congratulations on your Promotion!';
                    $url = url("/employee/my-greetings");
                    
                    if ($template && !empty($template->template_body)) {
                        $parsedBody = $template->template_body;
                        $parsedBody = str_replace('[NAME]', $userName, $parsedBody);
                        $parsedBody = str_replace('[COMPANY_NAME]', $newCompName, $parsedBody);
                        $parsedBody = str_replace('[OLD_DESIGNATION]', $oldDesigName, $parsedBody);
                        $parsedBody = str_replace('[NEW_DESIGNATION]', $newDesigName, $parsedBody);
                        $parsedBody = str_replace('[OLD_SALARY]', $oldSalaryVal, $parsedBody);
                        $parsedBody = str_replace('[NEW_SALARY]', $newSalary, $parsedBody);
                        $parsedBody = str_replace('[EFFECTIVE_DATE]', $effectiveDate, $parsedBody);
                    } else {
                        // Agar admin ne template save nahi kiya, toh system crash ya skip nahi hoga, ye default bheja jayega
                        $parsedBody = "Dear **{$userName}**,\n\nCongratulations! You have been successfully promoted to **{$newDesigName}** at {$newCompName}. Your new salary is **₹{$newSalary}**, effective from {$effectiveDateDb}.\n\nKeep up the excellent work!";
                    }

                 // Naya PromotionNotification bhej rahe hain (Employee aur Member dono loops me ye update karein)
                    $employee->notify(new PromotionNotification(
                        $title, 
                        $parsedBody, 
                        'fa-solid fa-trophy', 
                        'text-warning', 
                        $url,
                        $effectiveDateFormatted // 🔥 6th parameter: Yahan Effective Date bhej di
                    ));
                    
                }
            } else {
                // For Members
                foreach ($staffIds as $memId) {
                    $member = Member::findOrFail($memId);

                    // Template ke liye purana data save karna
                    $oldDesigName = $member->designation ? $member->designation->designation_name : 'N/A';
                    $oldSalaryVal = $member->current_salary ?? 0;
                    $userName = $member->member_name;

                    // A. Main Table Update
                    $member->company_id = $companyId;
                    $member->branch_id = $branchId;
                    $member->department_id = $departmentId;
                    $member->designation_id = $designationId;
                    $member->current_salary = $newSalary;
                    $member->save();

                    $company = \App\Models\Company::find($companyId);
                    $companyCode = $company ? $company->company_code : 'CMP';

                  // 1. Purane Member Service Record ko close karna (date_of_leaving update karna)
                    $lastMemberService = \App\Models\MemberServiceRecord::where('member_id_ref', $member->id)
                                    ->orderBy('id', 'desc')
                                    ->first();
                                    
                    if ($lastMemberService) {
                        // Nayi joining date se 1 din pehle ki date
                        $lastMemberService->date_of_leaving = \Carbon\Carbon::parse($effectiveDateDb)->subDay()->format('Y-m-d');
                        $lastMemberService->save();
                    }

                    // 2. Naya Member Service Record Insert karna
                    \App\Models\MemberServiceRecord::create([
                        'service_code' => $member->service_id, 
                        'member_id_ref' => $member->id,
                        'company_code' => $companyCode,
                        'action_type' => 'Promotion',
                        'action_date' => $effectiveDateDb, 
                        'promotion_date' => $effectiveDateDb, // 🔥 Yahan promotion_date add ho gaya
                        'action_details' => [
                            'designation_id' => $designationId,
                            'department_id' => $departmentId,
                            'branch_id' => $branchId,
                            'company_id' => $companyId
                        ],
                        'current_salary' => $newSalary,
                    ]);
                    
// 🔥 C. Parse & Dispatch Notification (WITH FALLBACK)
                    $title = $template->subject ?? 'Congratulations on your Promotion!';
                    $url = url("/member/my-greetings");
                    
                    if ($template && !empty($template->template_body)) {
                        $parsedBody = $template->template_body;
                        $parsedBody = str_replace('[NAME]', $userName, $parsedBody);
                        $parsedBody = str_replace('[COMPANY_NAME]', $newCompName, $parsedBody);
                        $parsedBody = str_replace('[OLD_DESIGNATION]', $oldDesigName, $parsedBody);
                        $parsedBody = str_replace('[NEW_DESIGNATION]', $newDesigName, $parsedBody);
                        $parsedBody = str_replace('[OLD_SALARY]', $oldSalaryVal, $parsedBody);
                      $parsedBody = str_replace('[NEW_SALARY]', $newSalary, $parsedBody);
                        $parsedBody = str_replace('[EFFECTIVE_DATE]', $effectiveDateFormatted, $parsedBody); // 🔥 Yahan update kiya
                    } else {
                        // Agar admin ne template save nahi kiya, toh system crash ya skip nahi hoga, ye default bheja jayega
                        $parsedBody = "Dear **{$userName}**,\n\nCongratulations! You have been successfully promoted to **{$newDesigName}** at {$newCompName}. Your new salary is **₹{$newSalary}**, effective from {$effectiveDate}.\n\nKeep up the excellent work!";
                    }

                    // Naya PromotionNotification bhej rahe hain
                    $member->notify(new PromotionNotification(
                        $title, 
                        $parsedBody, 
                        'fa-solid fa-trophy', 
                        'text-warning', 
                        $url
                    ));
                }
            }

            // 3. Sab Sahi Raha Toh Save Kar Do (Commit)
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => count($staffIds) . ' staff member(s) successfully promoted!'
            ]);

        } catch (\Exception $e) {
            // 4. Agar error aayi toh wapas pichli state me chale jao (Rollback)
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to promote: ' . $e->getMessage()
            ], 500);
        }
    }
}