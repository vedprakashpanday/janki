<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Designation;
use App\Models\ServiceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MediaConverterService;

class EmployeeController extends Controller
{
    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    private function generateSmartEmployeeId($companyId)
    {
        $companyCode = 'MST';
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) $companyCode = $company->company_code;
        }
        $prefix = $companyCode . '-A/';
        $lastEmp = Employee::where('member_id', 'LIKE', $prefix . '%')->orderByRaw('LENGTH(member_id) DESC')->orderBy('member_id', 'desc')->first();
        $nextSeq = 7;
        if ($lastEmp) {
            $lastSeq = (int) str_replace($prefix, '', $lastEmp->member_id);
            if ($lastSeq >= 7) $nextSeq = $lastSeq + 1;
        }
        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    // 🔥 NAYA JADOO: Auto-Rearrange IDs based on Date of Joining (DOJ) 🔥
    private function resequenceCompanyEmployees($companyId)
    {
        if (!$companyId) return;

        $companyCode = 'MST';
        $company = Company::find($companyId);
        if ($company) $companyCode = $company->company_code;

        $prefix = $companyCode . '-A/';
        $svcPrefix = $companyCode . '-A/SVC/';

        // Sabhi employees ko DOJ ke hisaab se line me lagao
        $employees = Employee::where('company_id', $companyId)
            ->orderBy('doj', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $updates = [];
        $seq = 7; // Series 007 se shuru ho rahi hai

        foreach ($employees as $emp) {
            $newMemberId = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $newServiceId = $svcPrefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            if ($emp->member_id !== $newMemberId) {
                // IMPORTANT CHECK: Agar service_id normal tareeke se bani thi, tabhi update karenge.
                // Agar ye transferred banda hai, to iski service_id purani wali hogi jo ki retain rakhni hai!
                $expectedOldServiceId = str_replace('-A/', '-A/SVC/', $emp->member_id);
                $shouldUpdateServiceId = ($emp->service_id === $expectedOldServiceId);

                $updates[] = [
                    'id' => $emp->id,
                    'old_member_id' => $emp->member_id,
                    'new_member_id' => $newMemberId,
                    'new_service_id' => $shouldUpdateServiceId ? $newServiceId : $emp->service_id,
                    'temp_member_id' => 'TMP-' . $emp->id . '-' . time() . rand(10, 99) // Database Unique Constraint error se bachne ke liye
                ];
            }
            $seq++;
        }

        // STEP 1: Pehle sabko Temporary ID de do, taaki duplication/unique error na aaye
        foreach ($updates as $up) {
            $old = $up['old_member_id'];
            $tmp = $up['temp_member_id'];

            EmployeeBankDetail::where('member_id', $old)->update(['member_id' => $tmp]);
            ServiceRecord::where('member_id', $old)->update(['member_id' => $tmp]);
            \App\Models\EmployeeLogin::where('user_id', $old)->update(['user_id' => $tmp]);
            DB::table('adm_regist')->where('id', $up['id'])->update(['member_id' => $tmp]);
        }

        // STEP 2: Ab finally sabko unki Nayi Ordered ID do
        foreach ($updates as $up) {
            $tmp = $up['temp_member_id'];
            $newMem = $up['new_member_id'];
            $newSvc = $up['new_service_id'];

            EmployeeBankDetail::where('member_id', $tmp)->update(['member_id' => $newMem]);
            ServiceRecord::where('member_id', $tmp)->update([
                'member_id' => $newMem,
                'service_id' => $newSvc
            ]);
            \App\Models\EmployeeLogin::where('user_id', $tmp)->update(['user_id' => $newMem]);
            DB::table('adm_regist')->where('id', $up['id'])->update([
                'member_id' => $newMem,
                'service_id' => $newSvc
            ]);
        }
    }

 public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Employee::with(['branch.company', 'designation']);

        // 1. Strict Scope Check (RBAC)
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee) {
                $query->where('company_id', $context->company_id)
                      ->where('branch_id', $context->branch_id);
            }
        }

        // 2. Multi-Select Filters (For Task Assignment)
        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        } elseif ($request->filled('company_id')) { // Fallback for normal datatable
            $query->where('company_id', $request->company_id);
        }

        // Branches WITH Head Office (HO) Magic
        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $hoCompanyIds = [];
            $normalBranchIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId); // Extract Company ID from HO_1
                } else {
                    $normalBranchIds[] = $bId; // Normal branch id
                }
            }

            $query->where(function($q) use ($normalBranchIds, $hoCompanyIds) {
                if (count($normalBranchIds) > 0) {
                    $q->whereIn('branch_id', $normalBranchIds);
                }
                if (count($hoCompanyIds) > 0) {
                    $q->orWhere(function($subQ) use ($hoCompanyIds) {
                        $subQ->whereIn('company_id', $hoCompanyIds)->whereNull('branch_id');
                    });
                }
            });
        } elseif ($request->filled('branch_id')) { // Fallback for normal datatable
            $query->where('branch_id', $request->branch_id);
        }

        // Department & Designation (Comma Separated)
        if ($request->filled('department_ids')) {
            $query->whereIn('department_id', explode(',', $request->department_ids));
        }
        if ($request->filled('designation_ids')) {
            $query->whereIn('designation_id', explode(',', $request->designation_ids));
        }

        // 3. Global Search (For DataTables)
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%")
                  ->orWhere('contact_no', 'LIKE', "%{$search}%");
            });
        }

        $totalData = Employee::count();
        $totalFiltered = $query->count();
        
        // 4. Pagination (-1 means all data, used in our task multi-select)
        if ($request->input('length', 10) != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->orderBy('doj', 'desc')->get() // Display the newest joiners first
        ]);
    }
    private function uploadFile($file, $prefix)
    {
        if (!$file) return null;
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp'])) {
            $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
            return $mediaRecord ? $mediaRecord->file_path : null;
        } else {
            $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/employees'), $filename);
            return 'uploads/employees/' . $filename;
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $context = $this->getGlobalContext();

            $companyId = $request->company_id;
            $branchId = $request->branch_id;

            if (!$context->is_god) {
                if ($context->is_director) {
                    $companyId = $context->company_id;
                } elseif ($context->is_employee) {
                    $companyId = $context->company_id;
                    $branchId = $context->branch_id;
                }
            }

            $hasDirect = $context->is_god;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                if (in_array('employee_add_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) {
                    $hasDirect = true;
                }
            }

            $finalStatus = $hasDirect ? ($request->emp_status ?? 'active') : 'pending';

            $memberId = $this->generateSmartEmployeeId($companyId);
            $serviceId = str_replace('-A/', '-A/SVC/', $memberId);

            if ($request->is_transfer == '1' && $request->transfer_old_id) {
                $oldEmp = Employee::find($request->transfer_old_id);
                if ($oldEmp && $oldEmp->service_id) {
                    $serviceId = $oldEmp->service_id;
                }
            }

            $firstName = explode(' ', $request->full_name)[0];
            $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
            $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no ?? '0000'), -4);
            $password = $namePart . '@' . $aadharPart;

            $employeeData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code', 'is_transfer', 'transfer_old_id']);
            if ($request->marital_status !== 'Married') $employeeData['anniversary_date'] = null;

            $employeeData['member_id'] = $memberId;
            $employeeData['service_id'] = $serviceId;
            $employeeData['company_id'] = $companyId ?: null;
            $employeeData['branch_id'] = $branchId ?: null;
            $employeeData['password'] = $password;
            $employeeData['emp_status'] = $finalStatus;
            $employeeData['role'] = $request->role ?? 'employee';

            $fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) $employeeData[$field] = $this->uploadFile($request->file($field), $field);
            }

            $employee = Employee::create($employeeData);

            if ($request->filled('account_no')) {
                EmployeeBankDetail::create(array_merge(['member_id' => $employee->member_id], $request->only(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code'])));
            }

            ServiceRecord::create([
                'user_id' => $employee->id,
                'member_id' => $employee->member_id,
                'service_id' => $employee->service_id,
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'department_id' => $employee->department_id,
                'designation_id' => $employee->designation_id,
                'joining_date' => $employee->doj,
                'role' => $employee->role,
                'status' => $finalStatus
            ]);

            if ($request->is_transfer == '1' && $request->transfer_old_id) {
                $oldEmp = Employee::find($request->transfer_old_id);
                if ($oldEmp) {
                    $oldEmp->emp_status = 'transferred';
                    $oldEmp->d_o_l = $request->doj;
                    $oldEmp->transferred_to_company = $companyId;
                    $oldEmp->save();
                    ServiceRecord::where('user_id', $oldEmp->id)->orderBy('id', 'desc')->update(['status' => 'transferred', 'date_of_leaving' => $request->doj]);
                }
            }

            // 🔥 YAHAN CALL KIYA HAI 🔥 : Data save hone ke baad auto arrange karo!
            $this->resequenceCompanyEmployees($companyId);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => $finalStatus === 'pending' ? "Employee Requested. Assigned to chronological timeline." : "Employee Generated Successfully. Inserted into timeline."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $employee = Employee::with(['branch.company', 'bankDetails', 'designation', 'department'])->find($id);

        if (!$context->is_god) {
            if ($context->is_director && $employee->company_id != $context->company_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            if ($context->is_employee && $employee->branch_id != $context->branch_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        if ($employee) {
            $serviceHistory = [];
            if ($employee->service_id) {
                $serviceHistory = \App\Models\ServiceRecord::where('service_id', $employee->service_id)
                    ->orderBy('joining_date', 'asc')
                    ->get();
            } else {
                $serviceHistory = \App\Models\ServiceRecord::where('user_id', $employee->id)->orderBy('joining_date', 'asc')->get();
            }

            $employee->service_history_data = $serviceHistory;

            return response()->json(['status' => 'success', 'data' => $employee]);
        }

        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $context = $this->getGlobalContext();
            $employee = Employee::find($id);
            if (!$employee) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

            $oldCompanyId = $employee->company_id; // Yaad rakhne ke liye agar company edit hui hai toh

            if (!$context->is_god) {
                if ($context->is_director && $employee->company_id != $context->company_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
                if ($context->is_employee && $employee->branch_id != $context->branch_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }

            $hasDirect = $context->is_god;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                if (in_array('employee_edit_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
            }

            $updateData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code', '_method', 'is_transfer', 'transfer_old_id']);
            if (!$hasDirect) $updateData['emp_status'] = 'pending';
            if ($request->marital_status !== 'Married') $updateData['anniversary_date'] = null;

            $fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) $updateData[$field] = $this->uploadFile($request->file($field), $field);
            }

            $employee->update($updateData);
            $newCompanyId = $employee->company_id;

            if ($request->filled('account_no')) {
                EmployeeBankDetail::updateOrCreate(['member_id' => $employee->member_id], $request->only(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code']));
            }

            $latestRecord = ServiceRecord::where('user_id', $employee->id)->orderBy('id', 'desc')->first();
            if ($latestRecord) {
                $latestRecord->status = $request->emp_status;
                if (in_array($request->emp_status, ['terminated', 'resigned', 'inactive', 'transferred'])) {
                    $latestRecord->date_of_leaving = $request->d_o_l;
                } else {
                    $latestRecord->date_of_leaving = null;
                }
                $latestRecord->joining_date = $employee->doj; // Doj update hua hai to record bhi update hoga
                $latestRecord->save();
            }

            // 🔥 YAHAN CALL KIYA HAI 🔥 : Update hone ke baad timeline wapas arrange karo!
            $this->resequenceCompanyEmployees($newCompanyId);
            // Agar company badli gayi hai (edit me), to purani company walo ko bhi theek karo
            if ($oldCompanyId && $oldCompanyId != $newCompanyId) {
                $this->resequenceCompanyEmployees($oldCompanyId);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => !$hasDirect ? "Edit Requested (Pending Approval)" : "Employee Updated & Timeline Auto-Arranged!"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $employee = Employee::find($id);

        if (!$context->is_god) {
            if ($context->is_director && $employee->company_id != $context->company_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            if ($context->is_employee && $employee->branch_id != $context->branch_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        if ($employee) {
            $memberId = $employee->member_id;
            $companyId = $employee->company_id;

            EmployeeBankDetail::where('member_id', $memberId)->delete();
            ServiceRecord::where('member_id', $memberId)->delete();
            \App\Models\EmployeeLogin::where('user_id', $memberId)->delete();
            $employee->delete();

            // Employee delete hua hai, toh numbers beech se toot jayenge, unhe wapas cover karne ke liye fir se sequence chala do
            $this->resequenceCompanyEmployees($companyId);

            return response()->json(['status' => 'success', 'message' => 'Employee deleted completely and timeline rearranged']);
        }
        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }

    public function searchForTransfer(Request $request)
    {
        $term = $request->keyword;
        $targetCompanyId = $request->target_company_id;

        if (!$term || !$targetCompanyId) return response()->json(['status' => 'error', 'message' => 'Please select a Company first and enter a keyword.']);

        $employees = Employee::with(['branch.company', 'designation', 'department', 'bankDetails'])
            ->where('emp_status', 'transferred')
            ->where('transferred_to_company', $targetCompanyId)
            ->where(function ($q) use ($term) {
                $q->where('member_id', 'LIKE', "%{$term}%")->orWhere('email', 'LIKE', "%{$term}%")->orWhere('contact_no', 'LIKE', "%{$term}%")->orWhere('aadhar_no', 'LIKE', "%{$term}%");
            })->limit(10)->get();

        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    public function getNextSmartId(Request $request)
    {
        $companyId = $request->company_id;
        return response()->json(['status' => 'success', 'next_id' => $this->generateSmartEmployeeId($companyId)]);
    }

    public function getPendingRequests(Request $request)
    {
        $context = $this->getGlobalContext();

        $query = Employee::with(['branch.company', 'designation', 'department'])->where('emp_status', 'pending')->latest();

        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee) {
                $query->where('branch_id', $context->branch_id);
            } else {
                $query->where('id', -1);
            }
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('designation_id')) $query->where('designation_id', $request->designation_id);

        $totalData = Employee::where('emp_status', 'pending')->count();

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('member_id', 'LIKE', "%{$search}%")
                    ->orWhere('contact_no', 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->get()
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $request->validate(['status' => 'required|in:active,inactive']);

        $hasPower = $context->is_god || $context->is_director;

        if (!$hasPower && method_exists(auth()->user(), 'getAllPermissions')) {
            $perms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            if ($request->status === 'active' && in_array('employee_approve', $perms)) {
                $hasPower = true;
            }
            if ($request->status === 'inactive' && in_array('employee_reject', $perms)) {
                $hasPower = true;
            }
        }

        if (!$hasPower) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have specific rights for this action.'], 403);
        }

        $employee = Employee::findOrFail($id);

        if (!$context->is_god) {
            if ($context->is_director && $employee->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            if ($context->is_employee && $employee->branch_id != $context->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $employee->emp_status = $request->status;
        $employee->save();

        $latestRecord = \App\Models\ServiceRecord::where('user_id', $employee->id)->orderBy('id', 'desc')->first();
        if ($latestRecord) {
            $latestRecord->status = $request->status;
            $latestRecord->save();
        }

        $actionWord = $request->status === 'active' ? 'Approved' : 'Rejected';
        return response()->json(['status' => 'success', 'message' => "Employee $actionWord Successfully!"]);
    }
}
