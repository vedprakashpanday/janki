<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Designation; // 🔥 FIX: Ye missing tha jiski wajah se 500 Error aaya!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Services\MediaConverterService;

class EmployeeController extends Controller
{
    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

   // ==========================================
    // GET All Employees (With Filters & RBAC)
    // ==========================================
    public function index(Request $request)
    {
        // 1. Eager Load relations ('designation' aayega, 'designationModel' nahi, jaisa pichle step me theek kiya tha)
        $query = \App\Models\Employee::with(['branch.company', 'designation']);

        // ========================================================
        // 🛡️ ZERO-TRUST RBAC SECURITY (LIVE DATA)
        // ========================================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director'])) {
                // CEO/Director sirf apni company ke employees dekhenge
                $query->where('company_id', $user->company_id);
            } else {
                // Branch Managers/HR sirf apni branch ke employees dekhenge
                $query->where('branch_id', $user->branch_id);
            }
        }

        // ========================================================
        // 🔥 FRONTEND TABLE FILTERS (Yahan purana JSON logic hata diya hai) 🔥
        // ========================================================
        
        // 1. Filter by Company
        if ($request->has('company_id') && $request->company_id != '') {
            $query->where('company_id', $request->company_id); // Direct ID match, json_contains NAHI
        }

        // 2. Filter by Branch
        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id); // Direct ID match
        }

        // 3. Search Bar Filter
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%")
                  ->orWhere('contact_no', 'LIKE', "%{$search}%");
            });
        }

        // Pagination and Data Fetching
        $totalData = \App\Models\Employee::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $employees = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $employees
        ]);
    }
    private function uploadFile($file, $prefix)
    {
        if (!$file) return null;
        $ext = strtolower($file->getClientOriginalExtension());
        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

        if (in_array($ext, $imageExts)) {
            $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
            return $mediaRecord ? $mediaRecord->file_path : null;
        } else {
            $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/employees'), $filename);
            return 'uploads/employees/' . $filename;
        }
    }

    // ==========================================
    // POST: Naya Employee Add karein
    // ==========================================
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $companyId = $request->company_id;
            $branchId = $request->branch_id;

            // ==========================================
            // 🛡️ SECURITY CHECK FOR CREATION
            // ==========================================
            $user = auth()->user();
            $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

            if (!in_array($user->email, $developerEmails)) {
                // Agar CEO hai, toh doosri company me add nahi kar sakta
                if ($user->hasRole(['CEO', 'Director']) && $companyId != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add employees to your own company.'], 403);
                }
                // Agar Employee hai, toh doosri branch me add nahi kar sakta
                if (!$user->hasRole(['CEO', 'Director']) && $branchId != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add employees to your own branch.'], 403);
                }
            }
            // ==========================================




            // 🔥 MASTER / COMPANY / BRANCH LOGIC FOR ID GENERATION 🔥
            if ($branchId) {
                $branch = Branch::with('company')->findOrFail($branchId);
                $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';
                $branchParts = explode('/', $branch->branch_id);
                $stateCode = $branchParts[1] ?? 'UNK';
                $rawBranchCode = $branchParts[2] ?? 'UNK';
            } elseif ($companyId) {
                $company = Company::findOrFail($companyId);
                $companyPrefix = $company->company_code;
                $stateCode = 'HO';
                $rawBranchCode = 'HO';
            } else {
                $companyPrefix = 'MST'; // Master Head Office
                $stateCode = 'HO';
                $rawBranchCode = 'HO';
            }

            $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode) ?: 'HO';
            $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);
            $formattedBranchNum = $branchNum ? str_pad($branchNum, 2, '0', STR_PAD_LEFT) : '00';

            // Designation
            $designation = Designation::where('designation_name', $request->designation)->first();
            $desigCode = $designation ? strtoupper($designation->designation_code) : strtoupper(substr($request->designation, 0, 3));

            // Sequence Generate Karna
            $lastEmployee = Employee::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->orderBy('id', 'desc')
                ->first();

            $empCount = $lastEmployee ? ((int) explode('/', $lastEmployee->member_id)[5] ?? 0) + 1 : 1;
            $sequence = str_pad($empCount, 3, '0', STR_PAD_LEFT);

            // Final ID
            $memberId = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/{$desigCode}/{$sequence}";

            // Password Generate
            $firstName = explode(' ', $request->full_name)[0];
            $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
            $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no), -4);
            $password = $namePart . '@' . $aadharPart;

            $employeeData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code']);

            // 🔥 NAYA: Anniversary Date Logic 🔥
            if ($request->marital_status !== 'Married') {
                $employeeData['anniversary_date'] = null;
            }

            $employeeData['member_id'] = $memberId;
            $employeeData['company_id'] = $companyId ?: null;
            $employeeData['branch_id'] = $branchId ?: null;
            $employeeData['password'] = $password;

            // Upload Files
            $fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $employeeData[$field] = $this->uploadFile($request->file($field), $field);
                }
            }

            $employee = Employee::create($employeeData);

            if ($request->filled('account_no')) {
                EmployeeBankDetail::create([
                    'member_id' => $employee->member_id,
                    'account_name' => $request->account_name,
                    'account_no' => $request->account_no,
                    'account_type' => $request->account_type,
                    'bank_name' => $request->bank_name,
                    'branch' => $request->bank_branch,
                    'ifsc_code' => $request->ifsc_code,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Employee created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $employee = Employee::with(['branch.company', 'bankDetails'])->find($id);

        // ==========================================
            // 🛡️ OWNERSHIP CHECK
            // ==========================================
            $user = auth()->user();
            $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

            if (!in_array($user->email, $developerEmails)) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another branch.'], 403);
                }
            }
            // ==========================================



        if (!$employee) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'data' => $employee]);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::find($id);
            if (!$employee) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

            // ==========================================
            // 🛡️ OWNERSHIP CHECK
            // ==========================================
            $user = auth()->user();
            $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

            if (!in_array($user->email, $developerEmails)) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another branch.'], 403);
                }
            }
            // ==========================================

            $updateData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code', '_method']);

            // 🔥 NAYA: Anniversary Date Logic 🔥
            if ($request->marital_status !== 'Married') {
                $updateData['anniversary_date'] = null;
            }

            $fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $updateData[$field] = $this->uploadFile($request->file($field), $field);
                }
            }

            $updateData['company_id'] = $request->company_id ?: null;
            $updateData['branch_id'] = $request->branch_id ?: null;

            $employee->update($updateData);

            if ($request->filled('account_no')) {
                EmployeeBankDetail::updateOrCreate(
                    ['member_id' => $employee->member_id],
                    $request->only(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code'])
                );
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Employee updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);

        // ==========================================
            // 🛡️ OWNERSHIP CHECK
            // ==========================================
            $user = auth()->user();
            $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

            if (!in_array($user->email, $developerEmails)) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This employee belongs to another branch.'], 403);
                }
            }
            // ==========================================


        if ($employee) {
            EmployeeBankDetail::where('member_id', $employee->member_id)->delete();
            $employee->delete();
            return response()->json(['status' => 'success', 'message' => 'Employee deleted']);
        }
        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }
}
