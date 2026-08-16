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
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // Null-Safe fallback
        $companyId = $context?->company_id ?? $user?->company_id ?? 1;
        $branchId = $context?->branch_id ?? $user?->branch_id;

        $userCompany = \App\Models\Company::find($companyId);
        $userBranch = $branchId ? \App\Models\Branch::find($branchId) : null;
        
        $isExecutive = $context?->is_god || $context?->role_level === 'ceo' || in_array($user?->email ?? '', ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']);

        return view('admin.debit_vouchers.index', [
            'prefix' => 'dv_',
            'source' => 'index',
            'companyId' => $companyId, // 🔥 Directly passing ID
            'branchId' => $branchId,   // 🔥 Directly passing ID
            'userCompany' => $userCompany,
            'userBranch' => $userBranch,
            'isExecutive' => $isExecutive,
            'isDirector' => $context?->is_director ?? false
        ]);
    }

    // 2. All Time Data / Directory ke liye (Prefix: dv_dir_)
    public function directory()
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // Null-Safe fallback
        $companyId = $context?->company_id ?? $user?->company_id ?? 1;
        $branchId = $context?->branch_id ?? $user?->branch_id;

        $userCompany = \App\Models\Company::find($companyId);
        $userBranch = $branchId ? \App\Models\Branch::find($branchId) : null;
        
        $isExecutive = $context?->is_god || $context?->role_level === 'ceo' || in_array($user?->email ?? '', ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']);

        return view('admin.debit_vouchers.index', [
            'prefix' => 'dv_dir_',
            'source' => 'directory',
            'companyId' => $companyId, // 🔥 Directly passing ID
            'branchId' => $branchId,   // 🔥 Directly passing ID
            'userCompany' => $userCompany,
            'userBranch' => $userBranch,
            'isExecutive' => $isExecutive,
            'isDirector' => $context?->is_director ?? false
        ]);
    }

    // Form dikhane ke liye
    public function create()
    {
        return view('admin.debit_vouchers.index');
    }

  public function print(Request $request, $id)
    {
        $voucher = \App\Models\DebitVoucher::findOrFail($id);
        $context = $this->getGlobalContext(); 
        
        $branch = $voucher->branch_id ? \App\Models\Branch::find($voucher->branch_id) : null;
        $company_id = $voucher->company_id ?? ($context->company_id ?? ($branch ? $branch->company_id : 1));
        $company = \App\Models\Company::find($company_id);

        $mode = $request->query('mode', 'print');

        // 🔥 NAYA: User Permissions Fetch karna
        $user = auth()->user();
        $canApprove = false;
        $canReject = false;
        if ($user) {
            $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];
            $canApprove = in_array('dv_appr', $permissions) || in_array('dv_dir_appr', $permissions);
            $canReject = in_array('dv_rej', $permissions) || in_array('dv_dir_rej', $permissions);
        }

        $compCode = $company ? ($company->company_code ?? 'COMP') : 'COMP';
        $branchCode = $branch ? ($branch->branch_code ?? 'BR') : 'H'; 
        $formattedDvNo = $compCode . '-' . $branchCode . '/' . str_pad($voucher->dv_no, 2, '0', STR_PAD_LEFT);
        
        $ledgerName = \Illuminate\Support\Facades\DB::table('ledgers')->where('ledger_code', $voucher->head_of_account)->value('ledger_name');
        $ledgerName = $ledgerName ? $ledgerName : $voucher->head_of_account; 

        $pId = $voucher->paid_to;
        $paidToName = $pId; 
        $nameMatches = [
            \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $pId)->value('full_name'),
            \Illuminate\Support\Facades\DB::table('members')->where('member_id', $pId)->value('member_name'),
            \Illuminate\Support\Facades\DB::table('vendors')->where('vendor_id', $pId)->value('full_name'),
            \Illuminate\Support\Facades\DB::table('agents')->where('agent_id', $pId)->value('full_name'),
            \Illuminate\Support\Facades\DB::table('landowners')->where('land_owner_id', $pId)->value('land_owner_name'),
            \Illuminate\Support\Facades\DB::table('directors')->where('director_id', $pId)->value('full_name'),
            \Illuminate\Support\Facades\DB::table('super_admins')->where('ceo_id', $pId)->value('full_name')
        ];
        
        foreach ($nameMatches as $name) {
            if (!empty($name)) {
                $paidToName = $name . ' (' . $pId . ')'; 
                break;
            }
        }

        $approverName = '';
        $employee = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $voucher->approved_by)->orWhere('id', $voucher->approved_by)->first();
        if ($employee) {
            $approverName = strtoupper($employee->full_name) . ' (' . $employee->member_id . ')';
        } else {
            $adminUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $voucher->approved_by)->first();
            if ($adminUser && $adminUser->email === 'admin@jankivilla.com') {
                $approverName = 'ACCOUNT(ABDPL)';
            } elseif ($adminUser) {
                $approverName = strtoupper($adminUser->name);
            } else {
                $approverName = $voucher->approved_by; 
            }
        }

        $signatoryName = '';
        $superAdmin = \Illuminate\Support\Facades\DB::table('super_admins')->where('ceo_id', $voucher->authorized_signatory)->orWhere('id', $voucher->authorized_signatory)->first();
        if ($superAdmin) {
            $signatoryName = strtoupper($superAdmin->full_name) . ' (' . $superAdmin->ceo_id . ')';
        } else {
            $signatoryName = $voucher->authorized_signatory; 
        }

        $paymentRef = '';
        if (strtoupper($voucher->payment_mode) === 'UPI') {
            $paymentRef = $voucher->pay_upi ?? $voucher->transaction_id;
        } else if (in_array(strtoupper($voucher->payment_mode), ['CHEQUE', 'BANK TRANSFER'])) {
            $paymentRef = $voucher->transaction_id;
        }

        $displayMode = strtoupper($voucher->payment_mode);
        if (!empty($voucher->type) && $displayMode === 'BANK TRANSFER') {
            $displayMode .= ' (' . strtoupper($voucher->type) . ')';
        }

        return view('admin.debit_vouchers.print', compact(
            'voucher', 'mode', 'company', 'branch', 
            'formattedDvNo', 'ledgerName', 'paidToName', 
            'approverName', 'signatoryName', 'paymentRef', 'displayMode',
            'canApprove', 'canReject' // 🔥 Pass these permissions
        ));
    }
}