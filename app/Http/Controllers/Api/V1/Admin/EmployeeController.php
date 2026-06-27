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

    // Auto-Rearrange IDs based on Date of Joining (DOJ)
    private function resequenceCompanyEmployees($companyId)
    {
        if (!$companyId) return;

        $companyCode = 'MST';
        $company = Company::find($companyId);
        if ($company) $companyCode = $company->company_code;

        $prefix = $companyCode . '-A/';
        $svcPrefix = $companyCode . '-A/SVC/';

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('doj', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $updates = [];
        $seq = 7;

        foreach ($employees as $emp) {
            $newMemberId = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $newServiceId = $svcPrefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            if ($emp->member_id !== $newMemberId) {
                $expectedOldServiceId = str_replace('-A/', '-A/SVC/', $emp->member_id);
                $shouldUpdateServiceId = ($emp->service_id === $expectedOldServiceId);

                $updates[] = [
                    'id' => $emp->id,
                    'old_member_id' => $emp->member_id,
                    'new_member_id' => $newMemberId,
                    'new_service_id' => $shouldUpdateServiceId ? $newServiceId : $emp->service_id,
                    'temp_member_id' => 'TMP-' . $emp->id . '-' . time() . rand(10, 99)
                ];
            }
            $seq++;
        }

        foreach ($updates as $up) {
            $old = $up['old_member_id'];
            $tmp = $up['temp_member_id'];

            EmployeeBankDetail::where('member_id', $old)->update(['member_id' => $tmp]);
            ServiceRecord::where('member_id', $old)->update(['member_id' => $tmp]);
            \App\Models\EmployeeLogin::where('user_id', $old)->update(['user_id' => $tmp]);
            DB::table('adm_regist')->where('id', $up['id'])->update(['member_id' => $tmp]);
        }

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

       // 🔥 FIX: Relation load karte waqt agar branch_id NULL hai toh use reject nahi karna hai
        $query = Employee::with([
            'company' => function ($q) {
                $q->where('status', 'active');
            },
            'branch' => function ($q) {
                // Yahan se strict where hatakar sirf active branch laane ka logic lagaya hai
                $q->where('branch_status', 'active');
            },
            'department' => function ($q) {
                $q->where('status', 'active');
            },
            'designation',
            'bankDetails' // 🔥 Isko bhi zaroor add karein taaki data aaye
        ]);
       // 🔥 1. Sirf ACTIVE employees ko lana hai
 

        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee) {
                $query->where('company_id', $context->company_id)
                    ->where('branch_id', $context->branch_id);
            }
        }

        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }



        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $hoCompanyIds = [];
            $normalBranchIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId);
                } else {
                    $normalBranchIds[] = $bId;
                }
            }

            $query->where(function ($q) use ($normalBranchIds, $hoCompanyIds) {
                if (count($normalBranchIds) > 0) {
                    $q->whereIn('branch_id', $normalBranchIds);
                }
                if (count($hoCompanyIds) > 0) {
                    $q->orWhere(function ($subQ) use ($hoCompanyIds) {
                        $subQ->whereIn('company_id', $hoCompanyIds)->whereNull('branch_id');
                    });
                }
            });
        } // 👇 3. BAS IS ELSEIF KO UPDATE KARNA HAI 👇
        elseif ($request->filled('branch_id')) {
            // Agar branch_id "HO" ya "HO_xx" aati hai (hamare TA form se)
            if ($request->branch_id === 'HO' || str_starts_with($request->branch_id, 'HO_')) {
                $query->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', '');
                });
            } else {
                $query->where('branch_id', $request->branch_id);
            }
        }

        // if ($request->filled('department_ids')) {
        //     $query->whereIn('department_id', explode(',', $request->department_ids));
        // }
        // if ($request->filled('designation_ids')) {
        //     $query->whereIn('designation_id', explode(',', $request->designation_ids));
        // }

       // 4. Department ID filter (Multi-Select Support)
        if ($request->filled('department_ids')) {
            $query->whereIn('department_id', explode(',', $request->department_ids));
        } elseif ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // 5. Designation ID filter (Multi-Select Support)
        if ($request->filled('designation_ids')) {
            $query->whereIn('designation_id', explode(',', $request->designation_ids));
        } elseif ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

          // 🔥 NAYA: Status filter support (Agar frontend se status aaye toh filter kare)
        if ($request->filled('status')) {
            $query->where('emp_status', $request->status);
        }

        // Uske baad aapka existing data fetch karne ka code hoga
        $employees = $query->get();

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

       // 🔥 BUG FIX: Limit tabhi lage jab datatable khud request kare (serverSide ke liye)
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->orderBy('doj', 'desc')->get()
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

           $employeeData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'branch', 'ifsc_code', 'is_transfer', 'transfer_old_id']);
unset($employeeData['bank_branch']);
unset($employeeData['branch']);
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
    $bankData = $request->only(['account_name', 'account_no', 'account_type', 'bank_name', 'ifsc_code']);
    
    // Yahan hum ensure kar rahe hain ki database ke 'branch' column mein hi data jaye
    $bankData['branch'] = $request->bank_branch ?? $request->branch; 

    EmployeeBankDetail::updateOrCreate(
        ['member_id' => $employee->member_id], 
        $bankData
    );
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

        // 🔥 FIX: Relations par active hone ka rule lagaya gaya
        $employee = Employee::with([
            'company' => function ($q) {
                $q->where('status', 'active');
            },
            'branch' => function ($q) {
                $q->where('branch_status', 'active');
            },
            'department' => function ($q) {
                $q->where('status', 'active');
            },
            'bankDetails',
            'designation'
        ])->find($id);

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

            $oldCompanyId = $employee->company_id;

            if (!$context->is_god) {
                if ($context->is_director && $employee->company_id != $context->company_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
                if ($context->is_employee && $employee->branch_id != $context->branch_id) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }

            $hasDirect = $context->is_god;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                if (in_array('employee_edit', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
            }

 // Yahan hum 'branch' aur 'bank_branch' dono ko filter kar rahe hain
            $updateData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'branch', 'ifsc_code', '_method', 'is_transfer', 'transfer_old_id']);

            // 🔥 EXTRA SAFETY: Array se forcefully remove kar rahe hain
            unset($updateData['bank_branch']);
            unset($updateData['branch']);

            // 🔥 BUG FIX: Agar JS galti se string 'null' bheje toh usko actual PHP null banayein
            foreach (['company_id', 'branch_id', 'department_id', 'designation_id'] as $fld) {
                if (isset($updateData[$fld]) && $updateData[$fld] === 'null') {
                    $updateData[$fld] = null;
                }
            }

if (!$hasDirect) $updateData['emp_status'] = 'pending';
            if ($request->marital_status !== 'Married') $updateData['anniversary_date'] = null;

            $fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'];
            // 🔥 BUG FIX: Pehle file fields ko array se hata dein taaki purani file null se overwrite na ho
foreach ($fileFields as $field) {
    unset($updateData[$field]); 
    if ($request->hasFile($field)) {
        $updateData[$field] = $this->uploadFile($request->file($field), $field);
    }
}
$employee->update($updateData);
            $newCompanyId = $employee->company_id;

     if ($request->filled('account_no')) {
    $bankData = $request->only(['account_name', 'account_no', 'account_type', 'bank_name', 'ifsc_code']);
    
    // Database table ke actual column 'branch' mein data bhej rahe hain
    $bankData['branch'] = $request->bank_branch ?? $request->branch;

    EmployeeBankDetail::updateOrCreate(
        ['member_id' => $employee->member_id], 
        $bankData
    );
}

            $latestRecord = ServiceRecord::where('user_id', $employee->id)->orderBy('id', 'desc')->first();
            if ($latestRecord) {
                $latestRecord->status = $request->emp_status;
                if (in_array($request->emp_status, ['terminated', 'resigned', 'inactive', 'transferred'])) {
                    $latestRecord->date_of_leaving = $request->d_o_l;
                } else {
                    $latestRecord->date_of_leaving = null;
                }
                $latestRecord->joining_date = $employee->doj;
                $latestRecord->save();
            }

            $this->resequenceCompanyEmployees($newCompanyId);
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

        // 🔥 FIX: Search query relations par active check laga diya gaya
        $employees = Employee::with([
            'company' => function ($q) {
                $q->where('status', 'active');
            },
            'branch' => function ($q) {
                $q->where('branch_status', 'active');
            },
            'department' => function ($q) {
                $q->where('status', 'active');
            },
            'designation',
            'bankDetails'
        ])
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

        // 🔥 FIX: Relations par active check laga diya gaya
        $query = Employee::with([
            'company' => function ($q) {
                $q->where('status', 'active');
            },
            'branch' => function ($q) {
                $q->where('branch_status', 'active');
            },
            'department' => function ($q) {
                $q->where('status', 'active');
            },
            'designation'
        ])->where('emp_status', 'pending')->latest();

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
