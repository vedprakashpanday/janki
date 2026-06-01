<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Branch;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    // ==========================================
    // 1. GET: Server-Side Data Load (10-10 Records)
    // ==========================================
    public function index(Request $request)
    {
       $query = Agent::with(['branch.company']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC 
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Manager ko sirf apni branch ke agents dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('full_name', 'LIKE', "%{$search}%")
                ->orWhere('agent_id', 'LIKE', "%{$search}%")
                ->orWhere('contact_no', 'LIKE', "%{$search}%");
        }

        $totalData = Agent::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $agents = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $agents
        ]);
    }

    // ==========================================
    // 2. STORE: Auto-Link Company ID & Generate ID
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required',
            'contact_no' => 'required',
            'joining_date' => 'required|date'
        ]);

        $data = $request->except(['_token']);

        // 1. Fetch Branch & Company
        $branch = Branch::with('company')->findOrFail($request->branch_id);

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add agents to your own branch.'], 403);
            }
        }
        // ==========================================

        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';
        $data['company_id'] = $branch->company_id; // Company ID DB me save hogi

        // 2. Format Branch Code (e.g. DAR/01)
        $branchParts = explode('/', $branch->branch_id);
        $stateCode = $branchParts[1] ?? 'ST';
        $rawBranchCode  = $branchParts[2] ?? 'DIST';

        $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode);
        $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);
        if (empty($branchNum)) {
            $branchNum = '1';
        }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);

        $year = date('Y', strtotime($request->joining_date));

       // 3. Generate Sequence Number
        $lastAgent = Agent::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastAgent && $lastAgent->agent_id) {
            $lastIdParts = explode('/', $lastAgent->agent_id);
            // Array format ho jayega: [CMP, ST, DIST, 01, AG, SEQ, YEAR]
            // Humara logic automatic aakhiri se dusra (Sequence) nikal lega
            $lastSeqStr = $lastIdParts[count($lastIdParts) - 2] ?? '0'; 
            $nextSeq = ((int) $lastSeqStr) + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        
        // 4. 🔥 Final Agent ID (Yahan 'AG' add kar diya gaya hai) 🔥
        $data['agent_id'] = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/AG/{$sequence}/{$year}";

        
        // Password Generation (Name@Aadhar)
        $firstName = explode(' ', $request->full_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // File Uploads
        $fileFields = [
            'aadhar_pdf',
            'pan_pdf',
            'bank_passbook_pdf',
            'driving_license_pdf',
            'passport_pdf',
            'passport_photo',
            'tenth_pdf',
            'twelfth_pdf',
            'graduation_pdf',
            'pg_pdf',
            'other_pdf',
            'nom_aadhar_pdf',
            'nom_pan_pdf',
            'nom_bank_passbook_pdf',
            'nom_driving_license_pdf',
            'nom_passport_pdf',
            'nom_passport_photo',
            'nom_tenth_pdf',
            'nom_twelfth_pdf',
            'nom_graduation_pdf',
            'nom_pg_pdf',
            'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/agents'), $filename);
                $data[$field] = 'uploads/agents/' . $filename;
            }
        }

        if (($data['agent_status'] ?? 'active') == 'active') {
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $agent = Agent::create($data);

        return response()->json([
            'status' => 'success',
            'message' => "Agent saved! ID: {$agent->agent_id}"
        ]);
    }

    // ==========================================
    // 3. SHOW: Include Company in Branch
    // ==========================================
    public function show($id)
    {
        $agent = Agent::with(['branch.company'])->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($agent->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $agent
        ]);
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($agent->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================
        $data = $request->except(['_token', 'agent_id', '_method']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = [ /* Same 22 fields */
            'aadhar_pdf',
            'pan_pdf',
            'bank_passbook_pdf',
            'driving_license_pdf',
            'passport_pdf',
            'passport_photo',
            'tenth_pdf',
            'twelfth_pdf',
            'graduation_pdf',
            'pg_pdf',
            'other_pdf',
            'nom_aadhar_pdf',
            'nom_pan_pdf',
            'nom_bank_passbook_pdf',
            'nom_driving_license_pdf',
            'nom_passport_pdf',
            'nom_passport_photo',
            'nom_tenth_pdf',
            'nom_twelfth_pdf',
            'nom_graduation_pdf',
            'nom_pg_pdf',
            'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/agents'), $filename);
                $data[$field] = 'uploads/agents/' . $filename;
            }
        }

        if (($data['agent_status'] ?? 'active') == 'active') {
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $agent->update($data);
        return response()->json(['status' => 'success', 'message' => 'Agent updated successfully']);
    }

    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($agent->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
