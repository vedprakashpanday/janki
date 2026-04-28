<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Letterhead;
use App\Models\Branch; // NAYA: Branch model import kiya
use Illuminate\Http\Request;

class LetterheadController extends Controller
{
    public function index()
    {
        $letterheads = Letterhead::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $letterheads]);
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/letterheads/images'), $filename);
            return response()->json(asset('uploads/letterheads/images/' . $filename));
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
        
        if(strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        // === NAYA AUTO REF NO GENERATOR (ABDPL/ST/DIST/01/2026) ===
        $branch = Branch::findOrFail($request->branch_id);
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
            if(isset($parts[3])) {
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
        return response()->json(['status' => 'success', 'data' => Letterhead::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->except(['_token', 'ref_no', '_method']);
        
        if(strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        Letterhead::findOrFail($id)->update($data);
        return response()->json(['status' => 'success', 'message' => 'Letterhead Updated Successfully']);
    }

    public function destroy($id)
    {
        Letterhead::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }

   // ========================================================
    // PRINT PREVIEW LOGIC (Laravel Query Builder - Fixes Table Name Errors)
    // ========================================================
    public function printPreview($id)
    {
        $letterhead = \App\Models\Letterhead::with('branch')->findOrFail($id);
        
        $empCode = $letterhead->emp_code;
        
        // Variables initialize kar rahe hain
        $paid_to_name = null;
        $paid_to_id = null;
        $paid_to_mobile = null;
        $paid_to_address = null;
        $paid_to_relation = null;
        $paid_to_doj = '-';
        $paid_to_designation = null;

        // Agar "All" nahi hai, tabhi database mein dhoondhenge
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

        // Blade View ko Data properly array format mein bhejna hai
        $records = [
            'ref_no' => $letterhead->ref_no,
            'letter_date' => $letterhead->letter_date,
            'letter_title' => $letterhead->subject ?? 'LETTERHEAD',
            'message' => $letterhead->message,
            'emp_code' => $letterhead->emp_code,
            
            // Agar employee id nahi mili toh form me bhara gaya manual 'paid_to' use hoga
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