<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Letterhead;
use App\Models\Branch;
use Illuminate\Http\Request;

class LetterheadController extends Controller
{
    public function index()
    {
        $query = Letterhead::with('branch')->orderBy('id', 'desc');

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee ko sirf apni branch ke letterheads dikhenge
            $query->where('branch_id', $user->branch_id);
        }
        // ==========================================

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function uploadImage(Request $request)
    {
        // TinyMCE default upload field name 'file' use karta hai
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/letterheads/images'), $filename);

            // 🔥 TINYMCE UPDATE: Response must be a JSON object with 'location' key
            return response()->json(['location' => asset('uploads/letterheads/images/' . $filename)]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ref_year' => 'required',
            'letter_date' => 'required',
            'message' => 'required'
        ]);

        $data = $request->except(['_token']);

        if (strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        // === NAYA AUTO REF NO GENERATOR (ABDPL/ST/DIST/01/2026) ===
        $branch = Branch::findOrFail($request->branch_id);

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only generate letterheads for your own branch.'], 403);
            }
        }
        // ==========================================

        $branchParts = explode('/', $branch->branch_id);
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';
        $year = $request->ref_year;

        // Is branch aur saal ka aakhiri record nikalenge
        $lastRecord = Letterhead::where('branch_id', $branch->id)
            ->where('ref_year', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastRecord && $lastRecord->ref_no) {
            // Pattern: ABDPL/ST/DIST/01/2026
            $parts = explode('/', $lastRecord->ref_no);
            if (isset($parts[3])) {
                $nextSeq = (int) $parts[3] + 1;
            }
        }

        $sequence = str_pad($nextSeq, 2, "0", STR_PAD_LEFT);
        $data['ref_no'] = "ABDPL/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        $letterhead = Letterhead::create($data);
        return response()->json(['status' => 'success', 'message' => "Letterhead Generated: {$letterhead->ref_no}"]);
    }

    public function show($id)
    {
        $letterhead = Letterhead::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($letterhead->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot access letterheads of another branch.'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => Letterhead::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->except(['_token', 'ref_no', '_method']);

        if (strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        $letterhead = Letterhead::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($letterhead->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot access letterheads of another branch.'], 403);
            }
        }

        // Update the record
        $letterhead->update($data);

        return response()->json(['status' => 'success', 'message' => 'Letterhead Updated Successfully']);
    }

    public function destroy($id)
    {
        $letterhead = Letterhead::findOrFail($id);

        // ==========================================
        // 🛡️ 3. OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($letterhead->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot access letterheads of another branch.'], 403);
            }
        }

        $letterhead->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }

    // ========================================================
    // PRINT PREVIEW LOGIC (Laravel Query Builder - Fixes Table Name Errors)
    // ========================================================
    public function printPreview($id)
    {
        $letterhead = \App\Models\Letterhead::with('branch')->findOrFail($id);

        // ==========================================
        // 🛡️ 4. PRINT OWNERSHIP CHECK (Web Route)
        // ==========================================
        $authUser = auth('sanctum')->user() ?? auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if ($authUser && !$authUser->hasRole(['CEO', 'Director']) && !in_array($authUser->email, $developerEmails)) {
            if ($letterhead->branch_id != $authUser->branch_id) {
                abort(403, 'Strict Security: You are not authorized to view or print letterheads of other branches.');
            }
        }
        // ==========================================

        $empCode = $letterhead->emp_code;

        $paid_to_name = null;
        $paid_to_id = null;
        $paid_to_mobile = null;
        $paid_to_address = null;
        $paid_to_relation = null;
        $paid_to_doj = '-';
        $paid_to_designation = null;

        if ($empCode && strtolower($empCode) !== 'all') {

            // 1. Check in Employees Table
            $emp = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $empCode)->first();
            if ($emp) {
                $paid_to_name = $emp->full_name ?? null;
                $paid_to_id = $emp->member_id;
                $paid_to_mobile = $emp->contact_no ?? $emp->mobile ?? null;
                $paid_to_address = $emp->communication_address ?? $emp->address ?? null;
                $paid_to_relation = $emp->father_spouse_name ?? null;
                $paid_to_doj = $emp->doj ?? '-';
                $paid_to_designation = $emp->designation ?? 'Employee';
            }

            // 2. Check in Members Table
            if (!$paid_to_name) {
                $mem = \Illuminate\Support\Facades\DB::table('members')->where('member_id', $empCode)->first();
                if ($mem) {
                    $paid_to_name = $mem->member_name ?? $mem->full_name ?? null;
                    $paid_to_id = $mem->member_id;
                    $paid_to_mobile = $mem->mobile ?? null;
                    $paid_to_address = $mem->address ?? null;
                    $paid_to_relation = $mem->so_do_name ?? $mem->father_spouse_name ?? null;
                    $paid_to_doj = $mem->doj ?? '-';
                    $paid_to_designation = $mem->designation ?? 'Member';
                }
            }

            // 3. Check in Customers Table
            if (!$paid_to_name) {
                $cust = \Illuminate\Support\Facades\DB::table('customers')->where('customer_id', $empCode)->first();
                if ($cust) {
                    $paid_to_name = $cust->customer_name ?? $cust->full_name ?? null;
                    $paid_to_id = $cust->customer_id;
                    $paid_to_mobile = $cust->mobile ?? $cust->customer_mobile ?? null;
                    $paid_to_address = $cust->address ?? null;
                    $paid_to_relation = $cust->so_do_wo ?? null;
                    $paid_to_doj = $cust->booking_date ?? '-';
                    $paid_to_designation = 'Customer';
                }
            }

            // 4. Check in Vendors Table
            if (!$paid_to_name) {
                $ven = \Illuminate\Support\Facades\DB::table('vendors')->where('vendor_id', $empCode)->first();
                if ($ven) {
                    $paid_to_name = $ven->full_name ?? $ven->vendor_name ?? null;
                    $paid_to_id = $ven->vendor_id;
                    $paid_to_mobile = $ven->contact_no ?? $ven->mobile ?? null;
                    $paid_to_address = $ven->communication_address ?? $ven->address ?? null;
                    $paid_to_relation = $ven->father_spouse_name ?? null;
                    $paid_to_designation = $ven->vendor_type ?? 'Vendor';
                }
            }

            // 5. Check in Landowners Table
            if (!$paid_to_name) {
                $land = \Illuminate\Support\Facades\DB::table('landowners')
                    ->where('landowner_id', $empCode)
                    ->orWhere('land_owner_id', $empCode)->first();
                if ($land) {
                    $paid_to_name = $land->landowner_name ?? $land->full_name ?? null;
                    $paid_to_id = $land->landowner_id ?? $land->land_owner_id ?? null;
                    $paid_to_mobile = $land->mobile1 ?? $land->mobile ?? null;
                    $paid_to_address = $land->address ?? null;
                    $paid_to_relation = $land->relation_name ?? null;
                    $paid_to_designation = 'Land Owner';
                }
            }
        }

        $records = [
            'ref_no' => $letterhead->ref_no,
            'letter_date' => $letterhead->letter_date,
            'letter_title' => $letterhead->subject ?? 'LETTERHEAD',
            'message' => $letterhead->message,
            'emp_code' => $letterhead->emp_code,

            'paid_to_name' => $paid_to_name ?: $letterhead->paid_to,
            'paid_to_id' => $paid_to_id,
            'paid_to_mobile' => $paid_to_mobile,
            'paid_to_address' => $paid_to_address ?: $letterhead->paid_to_address,
            'paid_to_relation' => $paid_to_relation,
            'paid_to_doj' => $paid_to_doj,
            'paid_to_designation' => $paid_to_designation
        ];

        return view('admin.print_letterhead', compact('records'));
    }
}
