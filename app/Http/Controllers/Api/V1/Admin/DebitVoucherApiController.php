<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DebitVoucher;
use Illuminate\Support\Facades\DB;



class DebitVoucherApiController extends Controller
{
    // 1. GET LIST (Datatable)
    public function index(Request $request)
    {
        $query = DebitVoucher::query();

        // Search logic
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('dv_no', 'LIKE', "%{$search}%")
                  ->orWhere('head_of_account', 'LIKE', "%{$search}%");
        }

        $totalData = DebitVoucher::count();
        $totalFiltered = $query->count();
        
        $vouchers = $query->offset($request->input('start'))
                          ->limit($request->input('length'))
                          ->orderBy('id', 'desc')
                          ->get();

        $data = $vouchers->map(function($v) {
            return [
                'id' => $v->id,
                'dv_no' => $v->dv_no,
                'voucher_date' => date('d-M-Y', strtotime($v->voucher_date)),
                'head_of_account' => $v->head_of_account,
                'amount' => $v->amount,
                'payment_mode' => strtoupper($v->payment_mode ?? 'CASH'),
                'action' => '
    <a href="/admin/debit_vouchers/print/'.$v->id.'?mode=view" target="_blank" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></a>
    <a href="/admin/debit_vouchers/print/'.$v->id.'?mode=print" target="_blank" class="btn btn-sm btn-light border ms-1 text-dark" title="Print"><i class="fas fa-print"></i></a>
    <button onclick="editVoucher('.$v->id.')" class="btn btn-sm btn-light border ms-1" title="Edit"><i class="fas fa-edit text-success"></i></button>
    <button onclick="deleteVoucher('.$v->id.')" class="btn btn-sm btn-light border ms-1" title="Delete"><i class="fas fa-trash text-danger"></i></button>
'
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }

    // 2. STORE NEW
    public function store(Request $request)
    {
        $request->validate([
            'voucher_date' => 'required|date',
            'head_of_account' => 'required'
        ]);

        try {
            // Auto generate DV NO agar user ne manual nahi dala ya "Auto-Generated" likha chhod diya
            $dv_no = $request->dv_no;
            if ($dv_no == 'Auto-Generated' || empty($dv_no)) {
                $lastDv = DebitVoucher::orderBy('id', 'desc')->first();
                $dv_no = $lastDv && is_numeric($lastDv->dv_no) ? $lastDv->dv_no + 1 : 1001;
            }

            $data = $request->all();
            $data['dv_no'] = $dv_no;

            DebitVoucher::create($data);

            return response()->json(['status' => 'success', 'message' => 'Voucher Created Successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 3. GET SINGLE RECORD (For Edit Modal)
    public function show($id)
    {
        $voucher = DebitVoucher::find($id);
        if (!$voucher) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $voucher]);
    }

    // 4. UPDATE RECORD
    public function update(Request $request, $id)
    {
        $voucher = DebitVoucher::find($id);
        $request->validate([
            'dv_no' => 'required|unique:debit_vouchers,dv_no,'.$id,
            'voucher_date' => 'required|date',
            'head_of_account' => 'required'
        ]);

        $voucher->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Voucher Updated Successfully!']);
    }

    // 5. DELETE RECORD
    public function destroy($id)
    {
        DebitVoucher::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully']);
    }


// app/Http/Controllers/Api/V1/Admin/DebitVoucherApiController.php

public function getMemberBankDetails(Request $request) {
    $memberId = $request->member_id;
    
    // tbl_bank_details se receiver ki saari bank details nikalna
    $bank = DB::table('tbl_bank_details')->where('member_id', $memberId)->first();
    
    if ($bank) {
        return response()->json([
            'status' => 'success',
            'data' => [
                'bank_name' => $bank->bank_name,
                'account_no' => $bank->account_no,
                'ifsc_code' => $bank->ifsc_code,
                'branch' => $bank->branch,
                'account_type' => $bank->account_type
            ]
        ]);
    } else {
        return response()->json([
            'status' => 'error', 
            'message' => 'Bank details not found'
        ]);
    }
}
// 2. Load Active Branches
public function getBranches() {
    $branches = DB::table('branches')->where('branch_status', 'active')->get();
    return response()->json(['status' => 'success', 'data' => $branches]);
}

// 3. Load Ledgers (Filtered by Branch)
public function getLedgers(Request $request) {
    $branchId = $request->branch_id;
    $ledgers = DB::table('ledgers')->where('status', 'Active')->where('branch_id', $branchId)->get();
    return response()->json(['status' => 'success', 'data' => $ledgers]);
}

// 4. Load Paid To UNION List (Filtered by Branch)
public function getPaidToList(Request $request) {
    $branchId = $request->branch_id;

    // Har table me hum branch_id se filter lagayenge
    $members = DB::table('members')->where('branch_id', $branchId)
        ->select('member_id as id', 'member_name as name', DB::raw("'member' as type"));
        
    $vendors = DB::table('vendors')->where('branch_id', $branchId)
        ->select('vendor_id as id', 'full_name as name', DB::raw("'vendor' as type"));
        
    $landowners = DB::table('landowners')->where('branch_id', $branchId)
        ->select('land_owner_id as id', 'land_owner_name as name', DB::raw("'landowner' as type"));
        
    $agents = DB::table('agents')->where('branch_id', $branchId)
        ->select('agent_id as id', 'full_name as name', DB::raw("'agent' as type"));

         $employee = DB::table('adm_regist')->where('branch_id', $branchId)
        ->select('member_id as id', 'full_name as name', DB::raw("'employee' as type"));

    // Union All Queries
    $list = $members->union($vendors)->union($landowners)->union($agents)->union($employee)->get();

    return response()->json(['status' => 'success', 'data' => $list]);
}

// 5. Check Real-time DV No Availability
public function checkDvNo(Request $request) {
    $exists = DB::table('debit_vouchers')->where('dv_no', $request->dv_no)->exists();
    return response()->json(['exists' => $exists]);
}



// Highest DV No find karne ke liye
public function getNextDvNo() {
    // String column me se numeric highest value nikalne ki query
    $maxDv = DB::table('debit_vouchers')
                ->select(DB::raw('MAX(CAST(dv_no AS UNSIGNED)) as max_dv'))
                ->first();
    
    // Agar koi entry hai to +1 kardo, warna 1 se start karo
    $nextDv = ($maxDv && $maxDv->max_dv) ? $maxDv->max_dv + 1 : 1;
    
    return response()->json(['next_dv' => $nextDv]);
}



public function getSenderBankDetails() {
    $senderId = 'ABA/BR/DAR1/001';
    // Is ID ke saare bank details fetch karein
    $banks = DB::table('tbl_bank_details')->where('member_id', $senderId)->get();
    
    if ($banks->count() > 0) {
        $data = $banks->map(function($bank) {
            return [
                'display_name' => $bank->bank_name . " (XXXX" . substr($bank->account_no, -4) . ")",
                'full_account_no' => $bank->account_no
            ];
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
    return response()->json(['status' => 'error', 'message' => 'No accounts found']);
}
}