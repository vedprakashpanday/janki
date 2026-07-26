<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DebitVoucher;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class DebitVoucherWebController extends Controller
{
   // 1. Current Date Data ke liye (Prefix: dv_)
    public function index()
    {
        return view('admin.debit_vouchers.index', [
            'prefix' => 'dv_',
            'source' => 'index'
        ]);
    }

    // 2. All Time Data / Directory ke liye (Prefix: dv_dir_)
    public function directory()
    {
        // Notice karein ki view same 'index' load ho raha hai, bas prefix aur source change ho gaye hain
        return view('admin.debit_vouchers.index', [
            'prefix' => 'dv_dir_',
            'source' => 'directory'
        ]);
    }
    // Form dikhane ke liye
    public function create()
    {
        return view('admin.debit_vouchers.index');
    }

    public function print(Request $request, $id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        
        $branch = $voucher->branch_id ? Branch::find($voucher->branch_id) : null;
        
        $company_id = $voucher->company_id ?? (auth()->user()->company_id ?? ($branch ? $branch->company_id : 1));
        $company = Company::find($company_id);

        $mode = $request->query('mode', 'print');

        // 🔥 NAYA LOGIC: Approved By aur Authorized Signatory ka naam nikalna
        $approverName = '';
        if ($voucher->approved_by) {
            $approver = DB::table('adm_regist')->where('id', $voucher->approved_by)->first();
            if ($approver) {
                $approverName = strtoupper($approver->full_name) . ' (' . strtoupper($approver->member_id) . ')';
            }
        }

        $signatoryName = '';
        if ($voucher->authorized_signatory) {
            $signatory = DB::table('adm_regist')->where('id', $voucher->authorized_signatory)->first();
            if ($signatory) {
                $signatoryName = strtoupper($signatory->full_name) . ' (' . strtoupper($signatory->member_id) . ')';
            }
        }

        // compact mein 'approverName' aur 'signatoryName' add kiya hai
        return view('admin.debit_vouchers.print', compact('voucher', 'mode', 'company', 'branch', 'approverName', 'signatoryName'));
    }
}