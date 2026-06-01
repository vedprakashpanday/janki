<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    // ==========================================
    // 1. GET: Server-Side Data Load (10-10 Records)
    // ==========================================
    public function index(Request $request)
    {
       $query = Member::with(['branch.company']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Manager ko sirf apni branch ke members dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('member_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%")
                  ->orWhere('sponsor_id', 'LIKE', "%{$search}%");
        }

        $totalData = Member::count();
        $totalFiltered = $query->count();
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $members = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $members
        ]);
    }

    
    // ==========================================
    // 2. STORE: Auto-Link Company ID, Sponsor Logic & Generate ID
    // ==========================================
    public function store(Request $request)
    {
        // 1. Validation (Designation ab text hai, ID nahi)
        $request->validate([
            'company_id' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required',
            'designation' => 'required', // Text name aayega frontend se
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ]);

        $data = $request->except(['_token']);

        $branch = \App\Models\Branch::with('company')->findOrFail($request->branch_id);

       
 // ==========================================
        // 🛡️ STORE/UPDATE OWNERSHIP & STRICT SPONSOR CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isAdmin = $user->hasRole(['CEO', 'Director', 'Branch Manager', 'super_admin', 'Developer']) || in_array(strtolower($user->email), $developerEmails);

        if (!$isAdmin) {
            // Normal Associate Login
            if (isset($branch) && $branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            
            // 🔥 FORCIBLY OVERRIDE SPONSOR ID FROM LOGGED IN USER 🔥
            $data['sponsor_id'] = $user->member_id;
            $data['sponsor_name'] = $user->name;
        } else {
            // Admin/Director/BM Logic
            if ($request->sponsor_name === 'SYSTEM ROOT') {
                $data['sponsor_name'] = 'SYSTEM ROOT';
            } elseif (!empty($request->sponsor_id)) {
                // 🔥 NAYA: Admin ne Sponsor ID bheja hai, toh Name DB se uthao 🔥
                $sponsor = \App\Models\Member::where('member_id', $request->sponsor_id)->first();
                $data['sponsor_name'] = $sponsor ? $sponsor->member_name : null;
            }
        }
        // ==========================================
        // ==========================================

        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';

        // Format Branch Code (e.g. MAD/01)
        $branchParts = explode('/', $branch->branch_id);
        $stateCode = $branchParts[1] ?? 'ST';
        $rawBranchCode = $branchParts[2] ?? 'DIST';
        
        $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode); 
        $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);   
        if (empty($branchNum)) { $branchNum = '1'; }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);

        // 🔥 NAYA LOGIC: Exact Designation Table se Code aur ID nikalna 🔥
        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        $data['designation_id'] = $designationObj ? $designationObj->id : null;
        $desigCode = $designationObj ? strtoupper($designationObj->designation_code) : 'MB';

        $year = date('Y', strtotime($request->doj));

        // Generate Sequence Number
        $lastMember = Member::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastMember && $lastMember->member_id) {
            $lastIdParts = explode('/', $lastMember->member_id);
            $lastSeqStr = $lastIdParts[count($lastIdParts) - 2] ?? '0'; 
            $nextSeq = ((int) $lastSeqStr) + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        
        // Final Member ID (e.g. ABD/BIH/MAD/01/MD/001/2026)
        $data['member_id'] = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/{$desigCode}/{$sequence}/{$year}";

        // Password Generation
        $firstName = explode(' ', $request->member_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // File Uploads
        $fileFields = [
            'aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign',
            'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
            'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
            'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
            'nom_pgcertificate', 'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/members'), $filename);
                $data[$field] = 'uploads/members/' . $filename;
            }
        }

        $member = Member::create($data);

        return response()->json([
            'status' => 'success', 
            'message' => "Member saved! ID: {$member->member_id}"
        ]);
    }

 public function show($id)
    {
        $member = Member::with(['branch.company'])->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($member->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json([
            'status' => 'success', 
            'data' => $member
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validation update
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation' => 'required',
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ]);

        $member = Member::findOrFail($id);
        
       
      // ==========================================
        // 🛡️ STORE/UPDATE OWNERSHIP & STRICT SPONSOR CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isAdmin = $user->hasRole(['CEO', 'Director', 'Branch Manager', 'super_admin', 'Developer']) || in_array(strtolower($user->email), $developerEmails);

        if (!$isAdmin) {
            // Normal Associate Login
            if (isset($branch) && $branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            
            // 🔥 FORCIBLY OVERRIDE SPONSOR ID FROM LOGGED IN USER 🔥
            $data['sponsor_id'] = $user->member_id;
            $data['sponsor_name'] = $user->name;
        } else {
            // Admin/Director/BM Logic
            if ($request->sponsor_name === 'SYSTEM ROOT') {
                $data['sponsor_name'] = 'SYSTEM ROOT';
            } elseif (!empty($request->sponsor_id)) {
                // 🔥 NAYA: Admin ne Sponsor ID bheja hai, toh Name DB se uthao 🔥
                $sponsor = \App\Models\Member::where('member_id', $request->sponsor_id)->first();
                $data['sponsor_name'] = $sponsor ? $sponsor->member_name : null;
            }
        }
        // ==========================================
        
        $data = $request->except(['_token', 'member_id', '_method']); 
        
        // 🔥 Strict Override: Member apne sponsor ko update nahi kar sakta 🔥
        if (!$isAdmin) {
            unset($data['sponsor_id']);
            unset($data['sponsor_name']);
        }

        // Designation Name se wapas ID fetch karo agar change hui hai toh
        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        if ($designationObj) {
            $data['designation_id'] = $designationObj->id;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // File Uploads
        $fileFields = [ 
            'aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign',
            'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
            'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
            'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
            'nom_pgcertificate', 'nom_otherdoc'
        ];
        
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/members'), $filename);
                $data[$field] = 'uploads/members/' . $filename;
            }
        }

        $member->update($data);
        return response()->json(['status' => 'success', 'message' => 'Member updated successfully.']);
    }

    public function destroy($id)
    {
       $member = Member::findOrFail($id);
    
    // ==========================================
    // 🛡️ OWNERSHIP CHECK
    // ==========================================
    $user = auth()->user();
    $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
    
    if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
        if ($member->branch_id != $user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }
    }
    // ==========================================
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}