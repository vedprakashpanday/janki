<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DebitVoucher;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use App\Models\Company; // Company model import karein
use App\Models\Branch;

class DebitVoucherApiController extends Controller
{
    // ==========================================
    // 🛡️ Safe Security Check
    // ==========================================
    private function isExecutiveAccess($user)
    {
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email ?? '', $developerEmails)) return true;

        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole(['CEO', 'Director', 'Super Admin'])) return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

  // ==========================================
    // 1. GET LIST (Datatable with Date Range, Scope & RBAC)
    // ==========================================
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Query initialize karein
            $query = DebitVoucher::query();

            // Permissions extract karein
            $permissions = [];
            if (method_exists($user, 'getAllPermissions')) {
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            }

            // Executive Access check (Admin/CEO)
            $isExecutive = $this->isExecutiveAccess($user);
            
            // Check agar user ke paas restore ki permission hai
            $canRestore = in_array('dv_restore', $permissions) || in_array('dv_dir_restore', $permissions);

            // ---------------------------------------------------------
            // 🛡️ FILTER 1: SOFT DELETES (Restore Visibility)
            // ---------------------------------------------------------
            if ($isExecutive || $canRestore) {
                // Admin ya jiske paas restore power hai, usko deleted data bhi dikhega
                $query->withTrashed();
            }

            // ---------------------------------------------------------
            // 🛡️ FILTER 2: DATA SCOPING (RBAC)
            // ---------------------------------------------------------
            if (!$isExecutive) {
                // Agar normal user hai, to sirf wo data dikhega jisme uska ID hai
                $memberId = $user->member_id ?? $user->id;
                $query->where(function ($q) use ($memberId) {
                    $q->where('approved_by', $memberId)
                      ->orWhere('emp_id', $memberId);
                });
            }

            // ---------------------------------------------------------
            // 🛡️ FILTER 3: DATE FILTER (Index vs Directory)
            // ---------------------------------------------------------
            // Frontend se hum pass karenge ki request Index se hai ya Directory se
            if ($request->input('source') === 'index') {
                // Sirf aaj ka data dikhayega
                $query->whereDate('created_at', date('Y-m-d'));
            }

            // Custom UI Date Range Filters
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('voucher_date', [$request->start_date, $request->end_date]);
            }

            // ---------------------------------------------------------
            // 🛡️ FILTER 4: SEARCH
            // ---------------------------------------------------------
            if ($request->has('search') && $request->input('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('dv_no', 'LIKE', "%{$search}%")
                      ->orWhere('head_of_account', 'LIKE', "%{$search}%");
                });
            }

            $totalData = DebitVoucher::count(); // Optional: You might want to scope this too
            $totalFiltered = $query->count();

            $vouchers = $query->offset($request->input('start', 0))
                ->limit($request->input('length', 10))
                ->orderBy('id', 'desc')
                ->get();

            $data = $vouchers->map(function ($v) {
                return [
                    'id' => $v->id,
                    'dv_no' => $v->dv_no,
                    'voucher_date' => date('d-M-Y', strtotime($v->voucher_date)),
                    'head_of_account' => $v->head_of_account,
                    'amount' => $v->amount,
                    'payment_mode' => strtoupper($v->payment_mode ?? 'CASH'),
                    'status' => ucfirst($v->status ?? 'pending'),
                    'deleted_at' => $v->deleted_at // JS me check karne ke liye ki row deleted hai ya nahi
                ];
            });

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => $totalData,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

 public function store(Request $request)
    {
        $request->validate([
            'dv_no' => 'required',
            'voucher_date' => 'required|date',
            'head_of_account' => 'required',
            'company_id' => 'required',
            'authorized_signatory' => 'required'
        ]);

        try {
            $user = auth()->user();
            $data = $request->all();

            // Branch HO Logic
           if (isset($data['branch_id']) && in_array($data['branch_id'], ['HO', 'null', ''])) {
    $data['branch_id'] = null; 
    $data['branch_name'] = 'Head Office';
} else if(isset($data['branch_id'])) {
                $branch = \App\Models\Branch::find($data['branch_id']);
                $data['branch_name'] = $branch ? $branch->branch_name : 'Unknown Branch';
            }

            // Unique Check for Company + Branch
            $exists = DebitVoucher::where('dv_no', $data['dv_no'])
                ->where('company_id', $data['company_id'])
                ->where('branch_id', $data['branch_id'])
                ->exists();
                
            if ($exists) {
                return response()->json(['status' => 'error', 'message' => 'This DV No is already taken for this Branch!'], 422);
            }

            $data['emp_id'] = $user->member_id ?? $user->id; 

            // RBAC Status Logic
            $status = 'pending';
            $approved_by = null;
            $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [];

            // Agar add_direct permission hai ya Executive (admin) hai
            if ($this->isExecutiveAccess($user) || in_array('dv_add_direct', $permissions) || in_array('dv_dir_add_direct', $permissions)) {
                $status = 'approved';
                $approved_by = $user->member_id ?? $user->id;
            }

            $data['status'] = $status;
            $data['approved_by'] = $approved_by;

            DebitVoucher::create($data);

            return response()->json(['status' => 'success', 'message' => 'Voucher Created! Status: ' . strtoupper($status)]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        $voucher = DebitVoucher::find($id);
        if (!$voucher) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        $user = auth()->user();
        if (!$this->isExecutiveAccess($user)) {
            if ($voucher->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Access.'], 403);
            }
        }
        return response()->json(['status' => 'success', 'data' => $voucher]);
    }

 public function update(Request $request, $id)
    {
        $voucher = DebitVoucher::find($id);
        $user = auth()->user();

        if (!$this->isExecutiveAccess($user)) {
            if ($voucher->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Access.'], 403);
            }
            if ($voucher->status === 'approved') {
                return response()->json(['status' => 'error', 'message' => 'Cannot edit an Approved Voucher.'], 403);
            }
        }

        $request->validate([
            'dv_no' => 'required|unique:debit_vouchers,dv_no,' . $id,
            'voucher_date' => 'required|date',
            'head_of_account' => 'required'
        ]);

        $data = $request->all();

        // 🔥 FIX: Voucher ko directly user ki company se link kar do
            $data['company_id'] = $user->company_id ?? 1; // Yeh line add karein

        // 🔥 FIX FOR UPDATE: Same logic for branch_name
       if (isset($data['branch_id']) && in_array($data['branch_id'], ['HO', 'null', ''])) {
    $data['branch_id'] = null; 
    $data['branch_name'] = 'Head Office';
} else {
                $branch = DB::table('branches')->where('id', $data['branch_id'])->first();
                $data['branch_name'] = $branch ? $branch->branch_name : 'Unknown Branch';
            }
        

        $voucher->update($data);
        return response()->json(['status' => 'success', 'message' => 'Voucher Updated Successfully!']);
    }

  // 🟢 Approve Action
    public function approve($id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $user = auth()->user();
        $voucher->update([
            'status' => 'approved',
            'approved_by' => $user->member_id ?? $user->id
        ]);
        return response()->json(['status' => 'success', 'message' => 'Voucher Approved Successfully!']);
    }

    // 🔴 Reject Action
    public function reject($id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $voucher->update(['status' => 'rejected']);
        return response()->json(['status' => 'success', 'message' => 'Voucher Rejected!']);
    }

    // 🟠 Cancel Action
    public function cancel($id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $voucher->update(['status' => 'cancelled']);
        return response()->json(['status' => 'success', 'message' => 'Voucher Cancelled!']);
    }

    // 🔵 Restore Action (Undo Soft Delete)
    public function restore($id)
    {
        $voucher = DebitVoucher::withTrashed()->findOrFail($id);
        $voucher->restore(); // Soft delete hatayega
        return response()->json(['status' => 'success', 'message' => 'Voucher Restored Successfully!']);
    }

    // 🗑️ Delete Action (Soft Delete) - Existing destroy ko replace karein
    public function destroy($id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $voucher->delete(); // Ye model me SoftDeletes hone ki wajah se permanently delete nahi karega
        return response()->json(['status' => 'success', 'message' => 'Voucher Moved to Trash (Soft Deleted)']);
    }
   
   // 🟢 FIX 2: Authorized Signatory Data Type (CEO-01 to Integer ID)
    public function getAuthorizedSignatories(Request $request)
    {
        try {
            $signatories = \App\Models\SuperAdmin::all();
            $data = $signatories->map(function ($admin) {
                return [
                    // Yahan hum ceo_id (string) ki jagah primary id (integer) use karenge
                    'id' => $admin->id, 
                    'name' => ($admin->full_name ?? $admin->name) . ' (CEO)'
                ];
            });
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    // (Baki Helper Functions Yahan as it is rahenge...)
    public function getMemberBankDetails(Request $request)
    {
        $bank = DB::table('tbl_bank_details')->where('member_id', $request->member_id)->first();
        if ($bank) return response()->json(['status' => 'success', 'data' => (array)$bank]);
        return response()->json(['status' => 'error', 'message' => 'Bank details not found']);
    }

    // Company Search
    public function searchCompanies(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json(['data' => []]);
        $companies = \App\Models\Company::where('company_name', 'LIKE', "%{$request->q}%")->limit(15)->get(['id', 'company_name']);
        return response()->json(['data' => $companies]);
    }

    // Branch Search
    public function searchBranches(Request $request)
    {
        if (strlen($request->q) < 3 || !$request->company_id) return response()->json(['data' => []]);
        $branches = \App\Models\Branch::where('company_id', $request->company_id)
            ->where('branch_name', 'LIKE', "%{$request->q}%")
            ->where('branch_status', 'active')
            ->limit(15)->get(['id', 'branch_name', 'branch_id']);
        return response()->json(['data' => $branches]);
    }

   // Ledger Search (Head of Account)
    public function searchLedgers(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json(['data' => []]);
        $query = \Illuminate\Support\Facades\DB::table('ledgers')->where('status', 'Active')
            ->where(function($q) use ($request) {
                $q->where('ledger_name', 'LIKE', "%{$request->q}%")
                  ->orWhere('ledger_code', 'LIKE', "%{$request->q}%");
            });
            
        if ($request->filled('branch_id') && $request->branch_id !== 'HO') {
            $query->where('branch_id', $request->branch_id);
        }
        
        return response()->json(['data' => $query->limit(15)->get()]);
    }

    // Paid To Search
    public function searchPaidTo(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json(['data' => []]);
        $q = $request->q;
        
        // Single optimized union query for searching across tables
        $members = \Illuminate\Support\Facades\DB::table('members')->select('member_id as id', 'member_name as name', \Illuminate\Support\Facades\DB::raw("'member' as type"))->where('member_name', 'LIKE', "%{$q}%");
        $vendors = \Illuminate\Support\Facades\DB::table('vendors')->select('vendor_id as id', 'full_name as name', \Illuminate\Support\Facades\DB::raw("'vendor' as type"))->where('full_name', 'LIKE', "%{$q}%");
        $employees = \Illuminate\Support\Facades\DB::table('adm_regist')->select('member_id as id', 'full_name as name', \Illuminate\Support\Facades\DB::raw("'employee' as type"))->where('full_name', 'LIKE', "%{$q}%");
        
        $results = $members->union($vendors)->union($employees)->limit(20)->get();
        return response()->json(['data' => $results]);
    }

 // 🟢 FIX 1: Unique DV No. Check (With strictly handled HO/null)
    public function checkDvNo(Request $request)
    {
        $query = DebitVoucher::where('dv_no', $request->dv_no)
                             ->where('company_id', $request->company_id);

        // String 'HO' ya 'null' ya empty value ko properly handle karein
        if (in_array($request->branch_id, ['HO', 'null', '', null], true)) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $request->branch_id);
        }

        // Edit ke waqt current voucher ko ignore karne ke liye
        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', $request->exclude_id);
        }

        return response()->json(['exists' => $query->exists()]);
    }

   // 🟢 FIX 2: Generate Next DV No. (Company & Branch Specific)
    public function getNextDvNo(Request $request)
    {
        $query = DB::table('debit_vouchers');

        // Company filter lagayein
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Branch filter lagayein (HO ke liye NULL check)
        if (in_array($request->branch_id, ['HO', 'null', '', null], true)) {
            $query->whereNull('branch_id');
        } else if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Sirf is specific Company aur Branch ka Max DV No nikalega
        $maxDv = $query->select(DB::raw('MAX(CAST(dv_no AS UNSIGNED)) as max_dv'))->first();
        
        return response()->json([
            'next_dv' => ($maxDv && $maxDv->max_dv) ? $maxDv->max_dv + 1 : 1
        ]);
    }
   // 🟢 FIX: Dynamic Sender Bank Search for 'CEO-' members
    public function getSenderBankDetails(Request $request)
    {
        $q = $request->q;
        
        $query = DB::table('tbl_bank_details')
                   ->where('member_id', 'LIKE', 'CEO-%');

        if (!empty($q)) {
            $query->where('bank_name', 'LIKE', "%{$q}%");
        }

        $banks = $query->limit(10)->get();

        if ($banks->count() > 0) {
            return response()->json(['status' => 'success', 'data' => $banks->map(function ($b) {
                // Bank Name (XXXX1234) format me return karega
                return [
                    'display_name' => $b->bank_name . " (XXXX" . substr($b->account_no, -4) . ")", 
                    'full_account_no' => $b->account_no
                ];
            })]);
        }
        return response()->json(['status' => 'error', 'message' => 'No accounts found']);
    }
public function print(Request $request, $id)
    {
        // 1. Voucher fetch karein
        $voucher = DebitVoucher::findOrFail($id);
        
        // 2. Global Context (optional, sirif baaki cheezon ke liye agar chahiye ho)
        $context = $this->getGlobalContext(); 
        
        // 3. Branch fetch karein (Agar HO hai to branch null hi rahegi, koi issue nahi)
        $branch = $voucher->branch_id ? Branch::find($voucher->branch_id) : null;
        
        // 🔥 NAYA LOGIC: Company direct voucher se uthao, agar purana record ho jisme company_id null thi, tab context/branch ka fallback use karo
        $company_id = $voucher->company_id ?? ($context->company_id ?? ($branch ? $branch->company_id : 1));
        
        $company = Company::find($company_id);

        $mode = $request->query('mode', 'print');

        return view('admin.debit_vouchers.print', compact('voucher', 'mode', 'company', 'branch'));
    }
}
