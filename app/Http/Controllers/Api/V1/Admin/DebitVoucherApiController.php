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
    // 1. GET LIST (Datatable with Date Range & Scope)
    // ==========================================
    public function index(Request $request)
    {
        try {
            $query = DebitVoucher::query();
            $user = auth()->user();

            // 🔥 NAYA: Date Range Filter
            if ($request->has('start_date') && $request->has('end_date') && !empty($request->start_date) && !empty($request->end_date)) {
                $query->whereBetween('voucher_date', [$request->start_date, $request->end_date]);
            }

            // 🔥 NAYA: Data Scope (Sirf Apna Banaya hua YA Apna Approve kiya hua dikhega)
            if (!$this->isExecutiveAccess($user)) {
                $query->where('branch_id', $user->branch_id ?? null)
                    ->where(function ($q) use ($user) {
                        $q->where('emp_id', $user->member_id ?? $user->id)
                            ->orWhere('approved_by', $user->id);
                    });
            }

            // Search logic
            if ($request->has('search') && $request->input('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('dv_no', 'LIKE', "%{$search}%")
                        ->orWhere('head_of_account', 'LIKE', "%{$search}%");
                });
            }

            $totalData = DebitVoucher::count();
            $totalFiltered = $query->count();

            $vouchers = $query->offset($request->input('start'))
                ->limit($request->input('length'))
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
                    'status' => ucfirst($v->status ?? 'pending') // 🔥 NAYA: Status bheja
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
            'voucher_date' => 'required|date',
            'head_of_account' => 'required',
            'authorized_signatory' => 'required'
        ]);

        try {
            $user = auth()->user();
            $dv_no = $request->dv_no;

            if ($dv_no == 'Auto-Generated' || empty($dv_no)) {
                $lastDv = DebitVoucher::orderBy('id', 'desc')->first();
                $dv_no = $lastDv && is_numeric($lastDv->dv_no) ? $lastDv->dv_no + 1 : 1001;
            }

            $data = $request->all();

            // 🔥 FIX: Voucher ko directly user ki company se link kar do
            $data['company_id'] = $user->company_id ?? null; // Yeh line add karein

            // 🔥 FIX: Branch ID aur Branch Name dono ko handle karna
            if (isset($data['branch_id'])) {
                if (!is_numeric($data['branch_id']) || $data['branch_id'] === 'HO') {
                    $data['branch_id'] = null; // 'HO' string ko null banaya taaki unsigned integer error na aaye
                    $data['branch_name'] = 'Head Office'; // Required field ko manual fill kiya
                } else {
                    // Valid ID hai, to DB se branch ka naam nikal lo
                    $branch = DB::table('branches')->where('id', $data['branch_id'])->first();
                    $data['branch_name'] = $branch ? $branch->branch_name : 'Unknown Branch';
                }
            }

            $data['dv_no'] = $dv_no;
            $data['emp_id'] = $user->member_id ?? $user->id; 

            // MAKER-CHECKER LOGIC
            $status = 'pending';
            $approved_by = null;

            $hasDirectAccess = $this->isExecutiveAccess($user);
            if (!$hasDirectAccess && method_exists($user, 'getAllPermissions')) {
                $perms = $user->getAllPermissions()->pluck('name')->toArray();
                if (in_array('debit_voucher_add_direct', $perms)) {
                    $hasDirectAccess = true;
                }
            }

            if ($hasDirectAccess) {
                $status = 'approved';
                $approved_by = $user->id;
            }

            $data['status'] = $status;
            $data['approved_by'] = $approved_by;

            DebitVoucher::create($data);

            return response()->json(['status' => 'success', 'message' => 'Voucher Submitted! Status: ' . strtoupper($status)]);
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
        if (isset($data['branch_id'])) {
            if (!is_numeric($data['branch_id']) || $data['branch_id'] === 'HO') {
                $data['branch_id'] = null; 
                $data['branch_name'] = 'Head Office';
            } else {
                $branch = DB::table('branches')->where('id', $data['branch_id'])->first();
                $data['branch_name'] = $branch ? $branch->branch_name : 'Unknown Branch';
            }
        }

        $voucher->update($data);
        return response()->json(['status' => 'success', 'message' => 'Voucher Updated Successfully!']);
    }

    public function destroy($id)
    {
        $voucher = DebitVoucher::find($id);
        $user = auth()->user();

        if (!$this->isExecutiveAccess($user)) {
            if ($voucher->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Access.'], 403);
            }
        }

        $voucher->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully']);
    }

    // ==========================================
    // 🔥 NAYA: Authorized Signatory API
    // ==========================================
    public function getAuthorizedSignatories(Request $request)
    {
        try {
            $user = auth()->user();
            $signatories = collect();

            // Agar Master Company / HO ka hai (CEO/Directors ko fetch karo)
            // Note: Hum assume kar rahe hain HO ka company_id '1' hai ya Executive hai
            if ($user->company_id == 1 || $this->isExecutiveAccess($user)) {
                $signatories = Employee::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['CEO', 'Director', 'Super Admin']);
                })->get();
            }
            // Agar normal branch se hai, toh us branch ke 'Accounts' walo ko fetch karo
            else {
                // Find department ID for 'Accounts'
                $accountsDept = DB::table('departments')->where('department_name', 'LIKE', '%Account%')->first();
                if ($accountsDept) {
                    $signatories = Employee::where('branch_id', $user->branch_id)
                        ->where('department_id', $accountsDept->id)
                        ->get();
                }
            }

            $data = $signatories->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => ($emp->full_name ?? $emp->employee_name) . ' (' . ($emp->designation_name ?? 'Accounts') . ')'
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

    public function getBranches()
    {
        $query = DB::table('branches')->where('branch_status', 'active');
        $user = auth()->user();
        if (!$this->isExecutiveAccess($user)) $query->where('company_id', $user->company_id ?? null);
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

   public function getLedgers(Request $request)
    {
        $query = DB::table('ledgers')->where('status', 'Active');
        
        // Agar branch aayi hai, to uske basis par filter karo, warna saare active ledgers bhej do
        if ($request->filled('branch_id')) {
            $bId = $request->branch_id;
            
            if (in_array(strtoupper($bId), ['HO', 'HEAD OFFICE', 'HEAD OFFICE (HO)'])) {
                $query->where(function($q) {
                    $q->whereNull('branch_id')
                      ->orWhere('branch_id', '')
                      ->orWhere('branch_id', '-')
                      ->orWhere('branch_id', 'HO');
                });
            } else {
                $query->where('branch_id', $bId);
            }
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function getPaidToList(Request $request)
    {
        $bId = $request->branch_id;

        $buildQuery = function($table, $idCol, $nameCol, $type) use ($bId) {
            $q = DB::table($table)->select("$idCol as id", "$nameCol as name", DB::raw("'$type' as type"));
            
            if (!empty($bId)) {
                if (in_array(strtoupper($bId), ['HO', 'HEAD OFFICE', 'HEAD OFFICE (HO)'])) {
                    $q->where(function($query) {
                        $query->whereNull('branch_id')
                              ->orWhere('branch_id', '')
                              ->orWhere('branch_id', '-')
                              ->orWhere('branch_id', 'HO');
                    });
                } else {
                    $q->where('branch_id', $bId);
                }
            }
            return $q;
        };

        $members = $buildQuery('members', 'member_id', 'member_name', 'member');
        $vendors = $buildQuery('vendors', 'vendor_id', 'full_name', 'vendor');
        $landowners = $buildQuery('landowners', 'land_owner_id', 'land_owner_name', 'landowner');
        $agents = $buildQuery('agents', 'agent_id', 'full_name', 'agent');
        $employee = $buildQuery('adm_regist', 'member_id', 'full_name', 'employee');

        return response()->json([
            'status' => 'success', 
            'data' => $members->union($vendors)->union($landowners)->union($agents)->union($employee)->get()
        ]);
    }

    public function checkDvNo(Request $request)
    {
        return response()->json(['exists' => DB::table('debit_vouchers')->where('dv_no', $request->dv_no)->exists()]);
    }

    public function getNextDvNo()
    {
        $maxDv = DB::table('debit_vouchers')->select(DB::raw('MAX(CAST(dv_no AS UNSIGNED)) as max_dv'))->first();
        return response()->json(['next_dv' => ($maxDv && $maxDv->max_dv) ? $maxDv->max_dv + 1 : 1]);
    }

    public function getSenderBankDetails()
    {
        $banks = DB::table('tbl_bank_details')->where('member_id', 'ABA/BR/DAR1/001')->get();
        if ($banks->count() > 0) {
            return response()->json(['status' => 'success', 'data' => $banks->map(function ($b) {
                return ['display_name' => $b->bank_name . " (XXXX" . substr($b->account_no, -4) . ")", 'full_account_no' => $b->account_no];
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
