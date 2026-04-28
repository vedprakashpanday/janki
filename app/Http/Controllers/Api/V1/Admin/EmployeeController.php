<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use App\Services\MediaConverterService;

class EmployeeController extends Controller
{
    protected $mediaConverter;

    // Constructor mein inject kiya
    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    // GET All Employees (with branch name)
    public function index()
    {
        $employees = Employee::with('branch', 'bankDetails')->latest()->get();
        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    // Helper: File Upload & WebP Conversion

// Helper: File Upload & WebP Conversion
    private function uploadFile($file, $prefix)
    {
        if (!$file) return null;

        $ext = strtolower($file->getClientOriginalExtension());
        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

        // 1. Agar IMAGE hai, toh aapke Service se convert/compress karein
        if (in_array($ext, $imageExts)) {
            $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
            return $mediaRecord ? $mediaRecord->file_path : null;
        } 
        // 2. Agar PDF ya dusra Document hai, toh DIRECT save karein
        else {
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
           // 1. Branch ka data fetch karein
            $branch = Branch::findOrFail($request->branch_id);

            // 2. Branch ID (e.g., JV/BR/DBG1/2025) me se DBG1 aur BR nikalna
            // explode() array banayega: [0=>'JV', 1=>'BR', 2=>'DBG1', 3=>'2025']
            $branchParts = explode('/', $branch->branch_id);
            $stateCode = $branchParts[1] ?? 'UNK';   // 'BR' nikal aayega
            $branchCode = $branchParts[2] ?? 'UNK';  // 'DBG1' nikal aayega

            // 3. Sabse Highest number (Sequence) nikalne ka logic
            $lastEmployee = \App\Models\Employee::where('branch_id', $branch->id)
                                                ->orderBy('id', 'desc')
                                                ->first();

            if ($lastEmployee && $lastEmployee->member_id) {
                // Agar employee hai, toh uski ID ko '/' se todenge aur aakhiri number nikalenge
                $lastIdParts = explode('/', $lastEmployee->member_id);
                $lastSequence = (int) end($lastIdParts); // end() array ka aakhiri item deta hai
                $empCount = $lastSequence + 1;
            } else {
                // Agar branch ekdum nayi hai aur koi employee nahi hai, toh 1 se start hoga
                $empCount = 1;
            }
            
            // Number ko 3 digit format me badalna (1 -> 001, 12 -> 012)
            // Note: Maine isko wapas 3 kar diya hai kyunki aapko 001 jaisa format chahiye tha
            $sequence = str_pad($empCount, 3, '0', STR_PAD_LEFT);

            // 4. Final Employee Code (e.g., ABA/BR/DBG1/001)
            $memberId = "ABA/{$stateCode}/{$branchCode}/{$sequence}";

            // Password Logic: Name(3) + @ + Aadhar(Last 4)
            $firstName = explode(' ', $request->full_name)[0];
            $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
            $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no), -4);
            $password = $namePart . '@' . $aadharPart;

            $employeeData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code']);
            
            // Generated ID aur Password set karein
            $employeeData['member_id'] = $memberId;
            $employeeData['password'] = $password;

            // --- ALL FILE FIELDS UPLOAD LOGIC ---
            $fileFields = [
                'passport_photo' => 'passport_photo', 
                'signature_photo' => 'signature_photo',
                'aadhar_pdf' => 'aadhar_pdf', 
                'pan_pdf' => 'pan_pdf', 
                'bank_passbook_pdf' => 'bank_passbook_pdf', 
                'driving_license_pdf' => 'driving_license_pdf',
                'passport_pdf' => 'passport_pdf', 
                'tenth_pdf' => 'tenth_pdf', 
                'twelfth_pdf' => 'twelfth_pdf', 
                'graduation_pdf' => 'graduation_pdf', 
                'pg_pdf' => 'pg_pdf', 
                'other_pdf' => 'other_pdf',
                // Nominee Files
                'nom_passport_photo' => 'nom_passport_photo',
                'nom_aadhar_pdf' => 'nom_aadhar_pdf', 
                'nom_pan_pdf' => 'nom_pan_pdf', 
                'nom_bank_passbook_pdf' => 'nom_bank_passbook_pdf', 
                'nom_driving_license_pdf' => 'nom_driving_license_pdf',
                'nom_passport_pdf' => 'nom_passport_pdf', 
                'nom_tenth_pdf' => 'nom_tenth_pdf', 
                'nom_twelfth_pdf' => 'nom_twelfth_pdf', 
                'nom_graduation_pdf' => 'nom_graduation_pdf', 
                'nom_pg_pdf' => 'nom_pg_pdf', 
                'nom_other_pdf' => 'nom_other_pdf'
            ];

            foreach ($fileFields as $formInput => $dbColumn) {
                if ($request->hasFile($formInput)) {
                    $employeeData[$dbColumn] = $this->uploadFile($request->file($formInput), $formInput);
                }
            }

            // Employee Create karein
            $employee = Employee::create($employeeData);

            // Bank Details Create karein
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

    // ==========================================
    // 1. SHOW: View / Edit ke liye data bhejna (GET)
    // ==========================================
    public function show($id)
    {
        // Employee ke sath branch aur bank details bhi fetch karke bhejo
        $employee = Employee::with('branch', 'bankDetails')->find($id);

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $employee]);
    }

    // ==========================================
    // 2. UPDATE: Edit save karna (PUT/PATCH via POST)
    // ==========================================
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::find($id);
            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
            }

            // Bank details ko alag karenge taaki directly employee table me na chali jayein
            $updateData = $request->except(['account_name', 'account_no', 'account_type', 'bank_name', 'bank_branch', 'ifsc_code', '_method']);

            // Saare file fields ki list
            $fileFields = [
                'passport_photo',  
                'signature_photo',
                'aadhar_pdf',
                'pan_pdf',
                'bank_passbook_pdf',
                'driving_license_pdf',
                'passport_pdf',
                'tenth_pdf',
                'twelfth_pdf',
                'graduation_pdf',
                'pg_pdf',
                'other_pdf',
                'nom_passport_photo',
                'nom_aadhar_pdf',
                'nom_pan_pdf',
                'nom_bank_passbook_pdf',
                'nom_driving_license_pdf',
                'nom_passport_pdf',
                'nom_tenth_pdf',
                'nom_twelfth_pdf',
                'nom_graduation_pdf',
                'nom_pg_pdf',
                'nom_other_pdf'
            ];

            // Agar user ne edit karte waqt koi nayi file/photo select ki hai, toh hi upload karo
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $updateData[$field] = $this->uploadFile($request->file($field), $field);
                }
            }

            // Employee Main Table Update
            $employee->update($updateData);

            // Bank Table Update (Agar data hai toh update karo, nahi toh Create karo)
            if ($request->filled('account_no')) {
                EmployeeBankDetail::updateOrCreate(
                    ['member_id' => $employee->member_id], // Find condition
                    [
                        'account_name' => $request->account_name,
                        'account_no' => $request->account_no,
                        'account_type' => $request->account_type,
                        'bank_name' => $request->bank_name,
                        'branch' => $request->bank_branch,
                        'ifsc_code' => $request->ifsc_code,
                    ] // Data to update
                );
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Employee updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. DESTROY: Employee Delete Karna (DELETE)
    // ==========================================
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if ($employee) {
            // Unlink/Delete files logic can be added here if you want to free server space

            // Uske bank details delete karo
            EmployeeBankDetail::where('member_id', $employee->member_id)->delete();

            // Main employee delete karo
            $employee->delete();
            return response()->json(['status' => 'success', 'message' => 'Employee deleted']);
        }

        return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
    }
}
