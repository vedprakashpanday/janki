<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\Branch;
use Illuminate\Http\Request;

class LandownerController extends Controller
{
   // ==========================================
    // 1. GET: Server-Side Data Load (10-10 Records)
    // ==========================================
    public function index(Request $request)
    {
        $query = Landowner::with(['branch.company']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Manager ko sirf apni branch ke landowners dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('land_owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('land_owner_id', 'LIKE', "%{$search}%")
                  ->orWhere('land_id', 'LIKE', "%{$search}%")
                  ->orWhere('mobile1', 'LIKE', "%{$search}%");
        }

        $totalData = Landowner::count();
        $totalFiltered = $query->count();
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $landowners = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $landowners
        ]);
    }

    // ==========================================
    // 2. STORE: Smart ID Generation (LO/LI) & Company
    // ==========================================
    public function store(Request $request)
    {
        // 🔥 NAYA: company_id aur phase_id validation me add kiya 🔥
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'phase_id' => 'required|exists:phases,id',
            'land_owner_name' => 'required',
            'mobile1' => 'required'
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
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add landowners to your own branch.'], 403);
            }
        }
        // ==========================================



        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';
        $data['company_id'] = $branch->company_id;

        // 2. Format Branch Code (e.g. DAR/01)
        $branchParts = explode('/', $branch->branch_id); 
        $stateCode = $branchParts[1] ?? 'ST';
        $rawBranchCode = $branchParts[2] ?? 'DIST';
        
        $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode); 
        $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);   
        if (empty($branchNum)) { $branchNum = '1'; }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);

        $year = date('Y');

        // 3. Generate Sequence
        $lastLO = Landowner::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastLO && $lastLO->land_owner_id) {
            $lastIdParts = explode('/', $lastLO->land_owner_id);
            // Dynamic sequence pickup (Second last part)
            $lastSeqStr = $lastIdParts[count($lastIdParts) - 2] ?? '0';
            $nextSeq = ((int) $lastSeqStr) + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        
        // 4. Final IDs (ABD/BIH/DAR/01/LO/001/2026 aur LI)
        $data['land_owner_id'] = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/LO/{$sequence}/{$year}";
        $data['land_id']       = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/LI/{$sequence}/{$year}";

        // File Uploads (20 Docs)
        $fileFields = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
            'khatiyaan_pdf', 'jamabandi_pdf', 'lo_agreement_pdf', 'registry_deed_pdf', 'link_deed_pdf', 'final_deed_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf', 'nom_passport_photo', 'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/landowners'), $filename);
                $data[$field] = 'uploads/landowners/' . $filename;
            }
        }

        $landowner = Landowner::create($data);
        return response()->json(['status' => 'success', 'message' => "Landowner saved! ID: {$landowner->land_owner_id}"]);
    }

    // ==========================================
    // 3. SHOW: Include Company in Branch
    // ==========================================
  public function show($id)
    {
        $landowner = Landowner::with(['branch.company'])->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($landowner->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $landowner]);
    }

    public function update(Request $request, $id)
    {
      $landowner = Landowner::findOrFail($id);

        // 🔥 NAYA: Update karte waqt bhi validation zaroori hai 🔥
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'phase_id' => 'required|exists:phases,id',
            'land_owner_name' => 'required',
            'mobile1' => 'required'
        ]);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($landowner->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        // $data array me automatic phase_id aa jayega kyunki humne explicitly usko form se bheja hai
        $data = $request->except(['_token', 'land_owner_id', 'land_id', '_method']);

        $fileFields = [ /* Same 20 files */
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
            'khatiyaan_pdf', 'jamabandi_pdf', 'lo_agreement_pdf', 'registry_deed_pdf', 'link_deed_pdf', 'final_deed_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf', 'nom_passport_photo', 'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/landowners'), $filename);
                $data[$field] = 'uploads/landowners/' . $filename;
            }
        }

        $landowner->update($data);
        return response()->json(['status' => 'success', 'message' => 'Landowner updated successfully']);
    }

    public function destroy($id)
    {
        

        $landowner = Landowner::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($landowner->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }


    // ==========================================
    // 🔥 AUTO-FILL SEARCH APIS 🔥
    // ==========================================

    public function searchCompany(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json([]);
        $companies = \App\Models\Company::where('company_name', 'LIKE', "%{$request->q}%")
            ->limit(10)->get(['id', 'company_name']);
        return response()->json($companies);
    }

    public function searchBranch(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json([]);
        $branches = \App\Models\Branch::where('company_id', $request->company_id)
            ->where('branch_name', 'LIKE', "%{$request->q}%")
            ->limit(10)->get(['id', 'branch_name']);
        return response()->json($branches);
    }

    public function searchPhase(Request $request)
    {
        if (strlen($request->q) < 2) return response()->json([]);
        $phases = \App\Models\Phase::with('company:id,company_name')
            ->where('company_id', $request->company_id)
            ->where('branch_id', $request->branch_id)
            ->where('phase_name', 'LIKE', "%{$request->q}%")
            ->limit(10)->get(['id', 'phase_name', 'company_id']);
        return response()->json($phases);
    }

    public function searchLandownersList(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json([]);
        $landowners = Landowner::where('company_id', $request->company_id)
            ->where('branch_id', $request->branch_id)
            ->where('phase_id', $request->phase_id)
            ->where('land_owner_name', 'LIKE', "%{$request->q}%")
            ->limit(10)->get(['id', 'land_owner_name', 'mobile1']);
        return response()->json($landowners);
    }


}