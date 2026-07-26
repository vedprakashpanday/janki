<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ledger;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        // Branch logic removed, fetch all ledgers
        $ledgers = Ledger::orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $ledgers]);
    }

    // NAYA: Code Generate karne ka API (Form me auto-fill ke liye)
    public function generateCode()
    {
        $lastLedger = Ledger::orderBy('id', 'desc')->first();
        $nextSeq = 1;

        if ($lastLedger && preg_match('/ABDPL-LED\/(\d+)/', $lastLedger->ledger_code, $matches)) {
            $nextSeq = (int)$matches[1] + 1;
        }

        $code = 'ABDPL-LED/' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        return response()->json(['status' => 'success', 'code' => $code]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ledger_name' => 'required',
            'ledger_code' => 'required|unique:ledgers,ledger_code'
        ]);

        $user = auth()->user();
        $status = 'Pending'; // Default fallback

        $data = $request->only(['ledger_name', 'ledger_code', 'from_date', 'to_date', 'status']);
        
        // Nayi lines add karein
        $data['phase_id'] = $request->has('add_phase_toggle') ? $request->phase_id : null;
        $data['company_id'] = $request->has('add_phase_toggle') ? $request->company_id : null;

        // 🛡️ RBAC LOGIC ON ADD
        // Dhyan de: Auth::user()->can() aapke RBAC package (jaise Spatie) par depend karta hai.
        if ($user->can('ledger_add_direct')) {
            $status = $request->status ?? 'Active'; // User ka choice manenge
        } elseif ($user->can('ledger_add_request')) {
            $status = 'Pending'; // Hamesha Pending
        } else {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $ledger = Ledger::create([
            'ledger_name' => $request->ledger_name,
            'ledger_code' => $request->ledger_code,
            'from_date'   => $request->from_date,
            'to_date'     => $request->to_date,
            'status'      => $status
        ]);

        return response()->json(['status' => 'success', 'message' => "Ledger Created! Code: {$ledger->ledger_code}"]);
    }

  public function show($id)
    {
        $ledger = Ledger::with(['phase', 'company'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $ledger]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ledger_name' => 'required',
            'ledger_code' => 'required|unique:ledgers,ledger_code,'.$id
        ]);

        $ledger = Ledger::findOrFail($id);
       $data = $request->only(['ledger_name', 'ledger_code', 'from_date', 'to_date', 'status']);
        
        // Nayi lines add karein
        $data['phase_id'] = $request->has('add_phase_toggle') ? $request->phase_id : null;
        $data['company_id'] = $request->has('add_phase_toggle') ? $request->company_id : null;
        
        $ledger->update($data);
        
        return response()->json(['status' => 'success', 'message' => 'Ledger Updated Successfully!']);
    }
    
    public function destroy($id)
    {
        Ledger::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Ledger Deleted Successfully!']);
    }

    // NAYA: Bulk Delete
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        Ledger::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected Ledgers Deleted Successfully!']);
    }

    // NAYA: Approve / Reject Status
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:approve,reject']);
        $ledger = Ledger::findOrFail($id);

        $user = auth()->user();

        if ($request->action === 'approve') {
            if (!$user->can('ledger_appr')) return response()->json(['message' => 'Unauthorized'], 403);
            $ledger->update(['status' => 'Active']);
            $msg = "Ledger Approved!";
        } else {
            if (!$user->can('ledger_rej')) return response()->json(['message' => 'Unauthorized'], 403);
            $ledger->update(['status' => 'Inactive']);
            $msg = "Ledger Rejected!";
        }

        return response()->json(['status' => 'success', 'message' => $msg]);
    }
}