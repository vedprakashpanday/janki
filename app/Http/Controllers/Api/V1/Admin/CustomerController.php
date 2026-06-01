<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
  // ==========================================
    // 1. GET: Server-Side Data Load (10-10 Records)
    // ==========================================
    public function index(Request $request)
    {
        $query = Customer::with(['branch.company']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee ko sirf apni company ke customers dikhenge
            $query->where('company_id', $user->company_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_id', 'LIKE', "%{$search}%")
                  ->orWhere('customer_mobile', 'LIKE', "%{$search}%");
        }

        $totalData = Customer::count();
        $totalFiltered = $query->count();
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $customers = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $customers
        ]);
    }

    // ==========================================
    // 2. STORE: Smart ID Generation (CUST)
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'customer_mobile' => 'required',
            'booking_date' => 'required|date',
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
            if ($branch->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot add a customer to another company\'s branch.'], 403);
            }
        }
        // ==========================================


        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';
        $data['company_id'] = $branch->company_id;

        // 2. Smart Branch Code Extraction
        $branchParts = explode('/', $branch->branch_id); 
        $stateCode = $branchParts[1] ?? 'ST';
        
        if (count($branchParts) >= 5) {
            $distCode = preg_replace('/[^a-zA-Z]/', '', $branchParts[2]); 
            $branchNum = preg_replace('/[^0-9]/', '', $branchParts[3]); 
        } else {
            $rawBranchCode = $branchParts[2] ?? 'DIST';
            $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode); 
            $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);   
        }

        if (empty($branchNum)) { $branchNum = '1'; }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);
        $year = date('Y', strtotime($request->booking_date));

        // 🔥 3. 100% BULLETPROOF SEQUENCE LOGIC 🔥
        $prefix = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/CUST/";
        
        $existingIds = Customer::where('customer_id', 'LIKE', $prefix . '%')->pluck('customer_id');
        $maxSeq = 0;
        foreach($existingIds as $cId) {
            $parts = explode('/', $cId);
            $seqStr = $parts[count($parts) - 2] ?? '0';
            $seqInt = (int)$seqStr;
            if($seqInt > $maxSeq) { $maxSeq = $seqInt; }
        }
        
        $nextSeq = $maxSeq + 1;
        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        
        // 4. Final ID Set (e.g. ABD/BIH/DAR/01/CUST/001/2026)
        $data['customer_id'] = "{$prefix}{$sequence}/{$year}";

        // Password Generation
        $firstName = explode(' ', $request->customer_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // File Upload Loop
        $fileFields = [
            'aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense', 'passport', 'passport_photo',
            'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
            'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
            'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
            'nom_pgcertificate', 'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer = Customer::create($data);
        return response()->json(['status' => 'success', 'message' => "Customer saved! ID: {$customer->customer_id}"]);
    }

    // ==========================================
    // 3. SHOW: Include Company in Branch
    // ==========================================
    public function show($id)
    {
        $customer = Customer::with(['branch.company'])->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($customer->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($customer->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }



      // Password ko allow kiya gaya hai, par 'customer_id' fixed rahega
$data = $request->except(['_token', 'customer_id', '_method']);

// Agar password field khali aayi hai, to purana password hi rehne do
if(empty($data['password'])) {
    unset($data['password']);
}

        // File Update Logic (Purani file delete nahi kar rahe abhi safety ke liye)
        $fileFields = [
            'aadharcard',
            'pancard',
            'bank_passbook_pdf',
            'drivinglicense',
            'passport',
            'passport_photo',
            'tenthmarksheet',
            'twelvethmarksheet',
            'graduationcertificate',
            'pgcertificate',
            'otherdoc',
            'nom_aadharcard',
            'nom_pancard',
            'nom_bankpassbook',
            'nom_drivinglicense',
            'nom_passport',
            'nom_passport_photo',
            'nom_tenthmarksheet',
            'nom_twelvethmarksheet',
            'nom_graduationcertificate',
            'nom_pgcertificate',
            'nom_otherdoc'
            ];


        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                
                // VS Code ko batane ke liye ki ye ek UploadedFile class hai
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer->update($data);
        return response()->json(['status' => 'success', 'message' => 'Customer updated']);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($customer->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }


        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
