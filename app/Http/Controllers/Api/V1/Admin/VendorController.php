<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Branch;
use Illuminate\Http\Request;

class VendorController extends Controller
{
   // ==========================================
    // 1. GET: Server-Side Data Load (10-10 Records)
    // ==========================================
    public function index(Request $request)
    {
        $query = Vendor::with(['branch.company']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC 
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Manager ko sirf apni branch ke vendors dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('vendor_id', 'LIKE', "%{$search}%")
                  ->orWhere('contact_no', 'LIKE', "%{$search}%");
        }

        $totalData = Vendor::count();
        $totalFiltered = $query->count();
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $vendors = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $vendors
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required',
            'contact_no' => 'required',
        ]);

        $data = $request->except(['_token']);

        // 1. Fetch Branch & Company
        $branch = \App\Models\Branch::with('company')->findOrFail($request->branch_id);

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add vendors to your own branch.'], 403);
            }
        }
        // ==========================================

        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';
        $data['company_id'] = $branch->company_id;

        // 2. Smart Branch Code Extraction (Dono formats ko samjhega)
        $branchParts = explode('/', $branch->branch_id); 
        $stateCode = $branchParts[1] ?? 'ST';
        
        if (count($branchParts) >= 5) {
            // Naya Format: ABD/BIH/DAR/01/2025
            $distCode = preg_replace('/[^a-zA-Z]/', '', $branchParts[2]); 
            $branchNum = preg_replace('/[^0-9]/', '', $branchParts[3]); 
        } else {
            // Purana Format: JV/BR/DBG1/2025
            $rawBranchCode = $branchParts[2] ?? 'DIST';
            $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode); 
            $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);   
        }

        if (empty($branchNum)) { $branchNum = '1'; }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);
        $year = date('Y');

        // 🔥 3. 100% BULLETPROOF SEQUENCE LOGIC 🔥
        $prefix = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/VD/";
        
        // Database se is prefix wali SAARI IDs nikal lo (e.g. ABD/BIH/DAR/01/VD/...)
        $existingIds = \App\Models\Vendor::where('vendor_id', 'LIKE', $prefix . '%')->pluck('vendor_id');
        
        $maxSeq = 0;
        foreach($existingIds as $vId) {
            $parts = explode('/', $vId);
            // Array: [ABD, BIH, DAR, 01, VD, 001, 2026] => Aakhiri se dusra element "001" hai
            $seqStr = $parts[count($parts) - 2] ?? '0';
            $seqInt = (int)$seqStr;
            
            // Sabse bada number dhundo
            if($seqInt > $maxSeq) {
                $maxSeq = $seqInt;
            }
        }
        
        // Sabse bade number me +1 kar do
        $nextSeq = $maxSeq + 1;
        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        
        // 4. Final ID Set (Ab ye kabhi duplicate nahi hogi)
        $data['vendor_id'] = "{$prefix}{$sequence}/{$year}";

        // ===============================================
        // Iske aage ka code aapka purana wala hi rahega
        // ===============================================
        
        // Password Generation
        $firstName = explode(' ', $request->full_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // File Uploads
        $fileFields = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_other_pdf'
        ];
        $imageFields = ['passport_photo', 'nom_passport_photo'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors'), $filename);
                $data[$field] = 'uploads/vendors/' . $filename;
            }
        }
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors/images'), $filename);
                $data[$field] = 'uploads/vendors/images/' . $filename;
            }
        }

        if(($data['vendor_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $vendor = Vendor::create($data);
        return response()->json(['status' => 'success', 'message' => "Vendor saved! ID: {$vendor->vendor_id}"]);
    }

    // ==========================================
    // 3. SHOW: Include Company in Branch
    // ==========================================
    public function show($id)
    {
        $vendor = Vendor::with(['branch.company'])->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($vendor->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $vendor]);
    }
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($vendor->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================
        $data = $request->except(['_token', 'vendor_id', '_method']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = ['aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'other_pdf', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_other_pdf'];
        $imageFields = ['passport_photo', 'nom_passport_photo'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors'), $filename);
                $data[$field] = 'uploads/vendors/' . $filename;
            }
        }

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                // SAME GLOBAL CONVERTER LOGIC YAHAN BHI LAGEGA
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors/images'), $filename);
                $data[$field] = 'uploads/vendors/images/' . $filename;
            }
        }

        if(($data['vendor_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $vendor->update($data);
        return response()->json(['status' => 'success', 'message' => 'Vendor updated successfully']);
    }

    public function destroy($id)
    {
       $vendor = Vendor::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($vendor->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}