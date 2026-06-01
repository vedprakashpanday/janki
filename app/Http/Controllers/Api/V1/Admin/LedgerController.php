<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ledger;
use App\Models\Branch;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        $query = Ledger::with('branch')->orderBy('id', 'desc');

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Accountant ko sirf apni branch ke ledgers dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        $ledgers = $query->get();
        return response()->json(['status' => 'success', 'data' => $ledgers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ledger_name' => 'required'
        ]);

        $data = $request->except(['_token', 'ledger_code']);

        // NAYA LOGIC: Year ko 'from_date' se nikalein, agar from_date nahi hai toh current year lein
        $year = !empty($request->from_date) ? date('Y', strtotime($request->from_date)) : date('Y');

       $branch = Branch::findOrFail($request->branch_id);

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only create ledgers for your own branch.'], 403);
            }
        }
        // ==========================================

        $branchParts = explode('/', $branch->branch_id);
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';

        // NAYA LOGIC: 'like' condition lagayi hai taaki ye sirf ussi saal (e.g. 2025) ke aakhiri record ko dhoonde
        $lastLedger = Ledger::where('branch_id', $branch->id)
                            ->where('ledger_code', 'like', "%/{$year}")
                            ->orderBy('id', 'desc')
                            ->first();
        
        $nextSeq = 1;
        
        if ($lastLedger && $lastLedger->ledger_code) {
            $lastIdParts = explode('/', $lastLedger->ledger_code);
            // Array Index 4 par hamara sequence number hota hai
            if(isset($lastIdParts[4])) {
                $nextSeq = ((int) $lastIdParts[4]) + 1;
            }
        }

        $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        $data['ledger_code'] = "JV/LEDG/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        $ledger = Ledger::create($data);
        return response()->json(['status' => 'success', 'message' => "Ledger Created! Code: {$ledger->ledger_code}"]);
    }

  public function show($id)
    {
        $ledger = Ledger::with('branch')->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($ledger->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot view ledgers of another branch.'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $ledger]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ledger_name' => 'required'
        ]);

       $ledger = Ledger::findOrFail($id);
        
        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($ledger->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify ledgers of another branch.'], 403);
            }
        }
        
        // Code update nahi hota, sirf baaki details update hoti hain (Best Practice)
        $data = $request->except(['_token', 'ledger_code', '_method']);
        
        $ledger->update($data);
        
        return response()->json(['status' => 'success', 'message' => 'Ledger Updated Successfully!']);
    }
    
    public function destroy($id)
    {
        $ledger = Ledger::findOrFail($id);
        
        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($ledger->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify ledgers of another branch.'], 403);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Ledger Deleted Successfully!']);
    }
}