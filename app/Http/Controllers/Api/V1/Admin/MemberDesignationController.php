<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberDesignation;
use App\Models\Branch; // Branch model import karna na bhoolein
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Rule import karein

class MemberDesignationController extends Controller
{
  public function index(Request $request)
    {
        $query = MemberDesignation::with('branch.company');

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC (Strict Backend)
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee/Manager ko sirf apni branch ki designations dikhengi
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

       // 🔥 NAYA: Branch Filter Logic 🔥
        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id);
        }

        // Search Logic
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('designation_code', 'LIKE', "%{$search}%")
                  ->orWhere('designation_name', 'LIKE', "%{$search}%");
            });
        }

        
        $totalData = MemberDesignation::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $designations = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $designations
        ]);
    }

   public function store(Request $request)
    {
        // Branch Wise Unique Validation
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation_code' => [
                'required', 'string', 'max:10',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)
            ],
            'designation_name' => [
                'required',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)
            ],
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

       // Branch fetch karke Company ID nikali
        $branch = Branch::findOrFail($request->branch_id);

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add designations to your own branch.'], 403);
            }
        }
        // ==========================================

        MemberDesignation::create([
            'company_id' => $branch->company_id,
            'branch_id' => $request->branch_id,
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Member Designation Added!']);
    }

    public function show($id)
    {
        $designation = MemberDesignation::with('branch.company')->findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation_code' => [
                'required', 'string', 'max:10',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)->ignore($id)
            ],
            'designation_name' => [
                'required',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)->ignore($id)
            ],
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

        $branch = Branch::findOrFail($request->branch_id);
       $designation = MemberDesignation::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================

        $designation->update([
            'company_id' => $branch->company_id,
            'branch_id' => $request->branch_id,
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Designation Updated!']);
    }

    public function destroy($id)
    {
       $designation = MemberDesignation::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }
        // ==========================================
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully!']);
    }
} 