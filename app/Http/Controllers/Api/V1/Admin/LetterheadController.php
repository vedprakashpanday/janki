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
        // 1. Validations (ref_no ki direct uniqueness hata di kyunki backend format banayega)
        $request->validate([
            'ref_no' => 'required', // Ye wo series (53) hai jo frontend se aayegi
            'ref_year' => 'required',
            'letter_date' => 'required',
            'message' => 'required'
        ]);

        // 2. Extract Company & Branch
        $companyId = ($request->company_id === 'global' || empty($request->company_id)) ? null : $request->company_id;
        $branchId = ($request->branch_id === 'all' || empty($request->branch_id)) ? null : $request->branch_id;

        $company = $companyId ? \App\Models\Company::find($companyId) : \App\Models\Company::whereNull('parent_id')->first();
        if (!$company) $company = \App\Models\Company::find(1); // Master Fallback

        // 3. Setup Default Codes
        $compCode = $company ? strtoupper($company->company_code) : 'COMP';
        $stateCode = 'ST';
        $distCode = 'DIST';
        $branchSeq = 'HO';

        // 4. Branch Hai Toh Uske Data Se Code Nikalo
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch && $branch->branch_id) {
                // Branch ID (e.g. COMP/ST/DIST/01/2026) me se hisse nikalo
                $bParts = explode('/', $branch->branch_id);
                $compCode = $bParts[0] ?? $compCode;
                $stateCode = $bParts[1] ?? 'ST';
                $distCode = $bParts[2] ?? 'DIST';
                $branchSeq = $bParts[3] ?? '01'; // Branch Number (e.g., 01, 02)
            }
        } else {
            // Head Office (HO) Logic: Company ke state/district se map karo
            $branchSeq = 'HO';
            
            if ($company) {
                $stateLower = strtolower(trim($company->state));
                $stateMap = ['bihar' => 'BIH', 'uttar pradesh' => 'UP', 'delhi' => 'DL', 'jharkhand' => 'JHA', 'west bengal' => 'WB'];
                $stateCode = $stateMap[$stateLower] ?? strtoupper(substr($stateLower, 0, 3));
                if (empty($stateCode)) $stateCode = 'ST';

                $distLower = strtolower(trim($company->district));
                $distMap = ['madhubani' => 'MAD', 'darbhanga' => 'DBJ', 'gopalganj' => 'GOPJ', 'saharsa' => 'SAH', 'patna' => 'PAT'];
                $distCode = $distMap[$distLower] ?? strtoupper(substr($distLower, 0, 3));
                if (empty($distCode)) $distCode = 'DIST';
            }
        }

        // 5. Build Final Reference Number String
        $series = $request->ref_no; // Frontend se aayi series (e.g., 53)
        $year = $request->ref_year;

        // FORMAT: COMPANY_CODE / STATE_CODE / DIST_CODE / HO_OR_SEQ / SERIES / YEAR
        $fullRefNo = "{$compCode}/{$stateCode}/{$distCode}/{$branchSeq}/{$series}/{$year}";

        // Manual Uniqueness Check
        if (\App\Models\Letterhead::where('ref_no', $fullRefNo)->exists()) {
            return response()->json(['status' => 'error', 'message' => "Reference Number '$fullRefNo' is already generated. Please refresh to get a new ID."], 400);
        }

        // 6. Data Preparation
        $data = $request->except(['_token', 'company_id', 'branch_id']);
        
        // Overwrite the simple series with the complex ref_no
        $data['ref_no'] = $fullRefNo; 
        $data['company_id'] = $companyId;
        $data['branch_id'] = $branchId;

        if (strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        // 7. Security Checks
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($companyId != $user->company_id || $branchId != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only generate letterheads for your own company/branch.'], 403);
            }
        }

        // 8. Save
        $letterhead = \App\Models\Letterhead::create($data);
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

        // Validation se theek pehle ya data extract karne ke baad:
if ($request->company_id === 'global') {
    $request->merge(['company_id' => null]);
}
if ($request->branch_id === 'all' || empty($request->branch_id)) {
    $request->merge(['branch_id' => null]); 
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
    // PRINT PREVIEW LOGIC
    // ========================================================
    public function printPreview($id)
    {
        $letterhead = \App\Models\Letterhead::with('branch', 'company')->findOrFail($id);

        // ==========================================
        // 🛡️ PRINT OWNERSHIP CHECK (Web Route)
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

            // 1. Check in Employees Table (FIXED WITH DESIGNATION MAPPING)
            $emp = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $empCode)->first();
            if ($emp) {
                $paid_to_name = $emp->full_name ?? null;
                $paid_to_id = $emp->member_id;
                $paid_to_mobile = $emp->contact_no ?? $emp->mobile ?? null;
                $paid_to_address = $emp->communication_address ?? $emp->address ?? null;
                $paid_to_relation = $emp->father_spouse_name ?? null;
                $paid_to_doj = $emp->doj ?? '-';
                
                // 🔥 NAYA FIX: designation_id se designations table se original naam nikalna
                $paid_to_designation = 'Employee'; // Default Fallback
                if (!empty($emp->designation_id)) {
                    $desg = \Illuminate\Support\Facades\DB::table('designations')->where('id', $emp->designation_id)->first();
                    if ($desg) {
                        $paid_to_designation = $desg->designation_name;
                    }
                }
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

        // Header Component settings
        if (empty($letterhead->company_id) || $letterhead->company_id === 'global') {
            $companyForHeader = \App\Models\Company::whereNull('parent_id')->first() ?? \App\Models\Company::find(1);
        } else {
            $companyForHeader = \App\Models\Company::find($letterhead->company_id);
        }

        if (empty($letterhead->branch_id) || $letterhead->branch_id === 'all') {
            $branchForHeader = null;
        } else {
            $branchForHeader = \App\Models\Branch::find($letterhead->branch_id);
        }

        return view('admin.print_letterhead', compact('records', 'companyForHeader', 'branchForHeader'));
    }
   // Frontend ko agla Number (Series) bhejne ke liye
    public function getNextRefNo()
    {
        $lastRecord = \App\Models\Letterhead::orderBy('id', 'desc')->first();
        $nextRef = 53; // Default starting series

        if ($lastRecord && $lastRecord->ref_no) {
            // Pattern check (e.g., ABDPL/BIH/PAT/HO/53/2026)
            $parts = explode('/', $lastRecord->ref_no);
            
            // Agar full format me hai (kam se kam 5 hisse hain), to 2nd last item series hogi
            if (count($parts) >= 5) {
                $seriesPart = $parts[count($parts) - 2]; 
                if (is_numeric($seriesPart)) {
                    $nextRef = intval($seriesPart) + 1;
                }
            } 
            // Agar purana record sirf number format me hai
            elseif (is_numeric($lastRecord->ref_no)) {
                $nextRef = intval($lastRecord->ref_no) + 1;
            }
        }

        return response()->json(['status' => 'success', 'next_ref_no' => $nextRef]);
    }
}
