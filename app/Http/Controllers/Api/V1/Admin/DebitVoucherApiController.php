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

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $data = $request->all();

            // 🔥 FIX: Salary fields ko alag variables me save kar lo aur $data se hata do
            $salaryMonth = $data['salary_month'] ?? null;
            $salaryId = $data['salary_id'] ?? null;
            $salaryPaymentType = $data['salary_payment_type'] ?? 'full';
            
            unset($data['salary_month'], $data['salary_id'], $data['salary_payment_type']);

            // 🔥 EXTRACTION 1: Paid To se sirf ID nikalna
            if (isset($data['paid_to']) && preg_match('/-\s*(.*?)\s*\[/', $data['paid_to'], $match)) {
                $data['paid_to'] = trim($match[1]); 
            }

            // 🔥 EXTRACTION 2: Head of Account se sirf Code nikalna
            if (isset($data['head_of_account']) && preg_match('/\((.*?)\)$/', $data['head_of_account'], $match)) {
                $data['head_of_account'] = trim($match[1]); 
            }

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

            if ($this->isExecutiveAccess($user) || in_array('dv_add_direct', $permissions) || in_array('dv_dir_add_direct', $permissions)) {
                $status = 'approved';
                // 🔥 VARCHAR TYPE SAVE
                $approved_by = $user->member_id ?? (string)$user->id; 
            }

            $data['status'] = $status;
            $data['approved_by'] = $approved_by;

            // Optional: Default Project Name agar empty aaye to
            if(empty($data['project_name'])) {
                $data['project_name'] = 'Janki Villa';
            }

            // DebitVoucher::create($data);// Create Voucher
            $voucher = DebitVoucher::create($data);

            // ==========================================
            // 🔥 EMPLOYEE LOAN / ADVANCE AUTO-SYNC LOGIC
            // ==========================================
            // Agar selected ledger 'SALARY ADVANCE A/C' hai jiska code ABDPL-LED/065 hai
            if ($data['head_of_account'] === 'ABDPL-LED/065') {
                $memberId = $data['paid_to']; // Pehle hi regex se extract ho chuka hai (e.g., ABDPL-A/0038)

                // Check agar employee ka koi active loan chal raha hai
                $activeLoan = \App\Models\EmployeeLoan::where('employee_id', $memberId)
                                ->where('status', 'active')
                                ->first();

                if ($activeLoan) {
                    // Agar active hai to purane balance me naya amount jod do
                    $activeLoan->total_amount += $voucher->amount;
                    $activeLoan->remaining_amount += $voucher->amount;
                    $activeLoan->debit_voucher_id = $voucher->id; // Update with latest voucher
                    $activeLoan->save();
                } else {
                    // Agar koi active loan nahi hai, to naya generate karo
                    $lastLoan = \App\Models\EmployeeLoan::orderBy('id', 'desc')->first();
                    $nextLoanNum = 1;
                    
                    if ($lastLoan && preg_match('/ABDPL-Adv\/(\d+)/', $lastLoan->loan_code, $matches)) {
                        $nextLoanNum = intval($matches[1]) + 1;
                    }
                    $loanCode = 'ABDPL-Adv/' . str_pad($nextLoanNum, 3, '0', STR_PAD_LEFT);

                    \App\Models\EmployeeLoan::create([
                        'company_id' => $voucher->company_id,
                        'branch_id' => $voucher->branch_id,
                        'loan_code' => $loanCode,
                        'employee_id' => $memberId,
                        'debit_voucher_id' => $voucher->id,
                        'total_amount' => $voucher->amount,
                        'paid_amount' => 0,
                        'remaining_amount' => $voucher->amount,
                        'status' => 'active'
                    ]);
                }
            }
// ==========================================
            // 🔥 3. SALARY PAYMENT AUTO-SYNC LOGIC (Replicate & Insert)
            // ==========================================
            if ($data['head_of_account'] === 'ABDPL-LED/063' && !empty($salaryId) && !empty($salaryMonth)) {
                
                $memberId = $data['paid_to']; 
                $employee = \App\Models\Employee::where('member_id', $memberId)->first();

                if ($employee) {
                    $salary = \App\Models\Salary::find($salaryId);

                    if ($salary) {
                        // Calculations
                        $newPaidAmount = ($salary->paid_amount ?? 0) + $voucher->amount;
                        $newLeftAmount = $salary->net_payable_salary - $newPaidAmount;
                        
                        // Status logic
                        $salaryStatus = ($newLeftAmount > 0) ? 'pending' : 'paid';

                        // 🔴 NAYA LOGIC: Purani row ko clone karo
                        $newSalary = $salary->replicate();
                        
                        // Clone ki hui nayi row me update karo
                        $newSalary->dv_no = $voucher->dv_no;
                        $newSalary->salary_payment_type = $salaryPaymentType;
                        $newSalary->paid_amount = $newPaidAmount;
                        $newSalary->left_amount = $newLeftAmount;
                        $newSalary->status = $salaryStatus;
                        
                        // Nayi row Insert kar do
                        $newSalary->save();

                        // 🔥 SAFETY RULE: Purani row ko 'archived' kar do taaki
                        // list me duplicate na dikhe aur next fetch me dikkat na ho.
                        $salary->update(['status' => 'archived']); 
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Voucher Created & Synced Successfully!']);
            
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }


  // 🟢 FIX 1: show() me relations load karein taaki edit me Company Name aaye
    public function show($id)
    {
        // with(['company', 'branch']) add karna zaroori hai
        $voucher = DebitVoucher::with(['company', 'branch'])->find($id);
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
            $data = $request->all();

            // 🔥 EXTRACTION 1: Paid To se sirf ID nikalna
            if (isset($data['paid_to']) && preg_match('/-\s*(.*?)\s*\[/', $data['paid_to'], $match)) {
                $data['paid_to'] = trim($match[1]); // e.g., 'ABDPL-A/0038'
            }

            // 🔥 EXTRACTION 2: Head of Account se sirf Code nikalna
            if (isset($data['head_of_account']) && preg_match('/\((.*?)\)$/', $data['head_of_account'], $match)) {
                $data['head_of_account'] = trim($match[1]); // e.g., 'ABDPL-LED/019'
            }

        if (!$this->isExecutiveAccess($user)) {
            if ($voucher->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Access.'], 403);
            }
            if ($voucher->status === 'approved') {
                return response()->json(['status' => 'error', 'message' => 'Cannot edit an Approved Voucher.'], 403);
            }
        }

        $request->validate([
            'dv_no' => 'required',
            'voucher_date' => 'required|date',
            'head_of_account' => 'required'
        ]);

        // 🔥 MANUAL UNIQUE CHECK FOR UPDATE (Company + Branch)
        $exists = DebitVoucher::where('dv_no', $data['dv_no'])
            ->where('company_id', $data['company_id'] ?? $voucher->company_id)
            ->where('branch_id', $data['branch_id'] ?? $voucher->branch_id)
            ->where('id', '!=', $id) // Current voucher ko ignore karega
            ->exists();
            
        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'This DV No is already taken for this Branch!'], 422);
        }

       

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
    public function approve(Request $request, $id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $user = auth()->user();
        
        $voucher->update([
            'status' => 'approved',
            'approved_by' => $user->member_id ?? (string)$user->id,
            'checker_remarks' => $request->checker_remarks // 🔥 Naya column update
        ]);
        
        return response()->json(['status' => 'success', 'message' => 'Voucher Approved Successfully!']);
    }

    // 🔴 Reject Action
    public function reject(Request $request, $id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        
        $voucher->update([
            'status' => 'rejected',
            'checker_remarks' => $request->checker_remarks // 🔥 Naya column update
        ]);
        
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
   
public function getAuthorizedSignatories(Request $request)
    {
        try {
            $signatories = \App\Models\SuperAdmin::all();
            $data = $signatories->map(function ($admin) {
                return [
                    'id' => $admin->ceo_id, 
                    // 🔥 Format Update: full_name (ceo_id)
                    'name' => ($admin->full_name ?? $admin->name) . ' (' . $admin->ceo_id . ')'
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

// 🟢 Ledger Search (Head of Account) with Strict Company & Global (null) Rule
    public function searchLedgers(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json(['data' => []]);

        $user = auth()->user();
        
        $query = \Illuminate\Support\Facades\DB::table('ledgers')->where('status', 'Active')
            ->where(function($q) use ($request) {
                $q->where('ledger_name', 'LIKE', "%{$request->q}%")
                  ->orWhere('ledger_code', 'LIKE', "%{$request->q}%");
            });
            
        // Rule: Ya to form ki lock ki hui company ID lo, ya user ki company ID (Parent 1)
        $companyId = $request->filled('company_id') ? $request->company_id : ($user->company_id ?? 1);

        // 🔥 NAYA RULE: Sirf wahi ledgers dikhenge jinki company_id NULL ho YA current companyId se match ho
        $query->where(function($q) use ($companyId) {
            $q->whereNull('company_id')
              ->orWhere('company_id', $companyId);
        });
            
        // // Agar branch locked hai to us specific branch ya HO (null) ke ledgers filter karo
        // if ($request->filled('branch_id') && !in_array($request->branch_id, ['HO', 'null', ''])) {
        //     $query->where(function($q) use ($request) {
        //         $q->whereNull('branch_id')
        //           ->orWhere('branch_id', $request->branch_id);
        //     });
        // }
        
        return response()->json(['data' => $query->limit(15)->get()]);
    }
    // Paid To Search
    public function searchPaidTo(Request $request)
    {
        if (strlen($request->q) < 3) return response()->json(['data' => []]);
        $q = $request->q;
        
        // Single optimized union query for searching across tables
        $members = \Illuminate\Support\Facades\DB::table('members')
        ->select('member_id as id', 'member_name as name',
         \Illuminate\Support\Facades\DB::raw("'member' as type"))
         ->where('member_name', 'LIKE', "%{$q}%");

        $vendors = \Illuminate\Support\Facades\DB::table('vendors')->select('vendor_id as id', 'full_name as name', \Illuminate\Support\Facades\DB::raw("'vendor' as type"))->where('full_name', 'LIKE', "%{$q}%");

        $employees = \Illuminate\Support\Facades\DB::table('adm_regist')
        ->select('member_id as id', 'full_name as name',
         \Illuminate\Support\Facades\DB::raw("'employee' as type"))
         ->where('full_name', 'LIKE', "%{$q}%");
        
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
  
    // 🟢 FIX 2: Sender's Bank API (Bank Name + Member ID return karega aur dono me search karega)
    public function getSenderBankDetails(Request $request)
    {
        $q = $request->q;
        
        $query = DB::table('tbl_bank_details')
                   ->where('member_id', 'LIKE', 'CEO-%');

        if (!empty($q)) {
            // Dono columns me search karne ke liye Grouping karna zaroori hai
            $query->where(function($subQuery) use ($q) {
                $subQuery->where('member_id', 'LIKE', "%{$q}%")
                         ->orWhere('bank_name', 'LIKE', "%{$q}%");
            });
        }

        $banks = $query->limit(10)->get();

        if ($banks->count() > 0) {
            return response()->json(['status' => 'success', 'data' => $banks->map(function ($b) {
                return [
                    // Suggestion me "Bank Name (CEO-01)" dikhayega
                    'display_name' => $b->bank_name . " (" . $b->member_id . ")", 
                    'bank_name' => $b->bank_name
                ];
            })]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'No accounts found']);
    }

    
public function print(Request $request, $id)
    {
        $voucher = DebitVoucher::findOrFail($id);
        $context = $this->getGlobalContext(); 
        
        $branch = $voucher->branch_id ? Branch::find($voucher->branch_id) : null;
        $company_id = $voucher->company_id ?? ($context->company_id ?? ($branch ? $branch->company_id : 1));
        $company = Company::find($company_id);

        $mode = $request->query('mode', 'print');

        // 1. DV No Formatting (e.g. ABDPL-H/01 or ABDPL-JHA/02)
        $compCode = $company ? ($company->company_code ?? 'COMP') : 'COMP';
        
        // Agar branch null hai to 'H' (Head Office), warna branch ka code
        $branchCode = $branch ? ($branch->branch_code ?? 'BR') : 'H'; 

        // Naya Format: COMPANY_CODE-BRANCH_CODE/DV_NO
        $formattedDvNo = $compCode . '-' . $branchCode . '/' . str_pad($voucher->dv_no, 2, '0', STR_PAD_LEFT);
        // 2. Head of Account Name Mapping
        $ledgerName = DB::table('ledgers')->where('ledger_code', $voucher->head_of_account)->value('ledger_name');
        $ledgerName = $ledgerName ? $ledgerName : $voucher->head_of_account; // Fallback

        // 3. Paid To Name Mapping (Checking all tables)
        $pId = $voucher->paid_to;
        $paidToName = $pId; // Default fallback
        $nameMatches = [
            DB::table('adm_regist')->where('member_id', $pId)->value('full_name'),
            DB::table('members')->where('member_id', $pId)->value('member_name'),
            DB::table('vendors')->where('vendor_id', $pId)->value('full_name'),
            DB::table('agents')->where('agent_id', $pId)->value('full_name'),
            DB::table('landowners')->where('land_owner_id', $pId)->value('land_owner_name'),
            DB::table('directors')->where('director_id', $pId)->value('full_name'),
            DB::table('super_admins')->where('ceo_id', $pId)->value('full_name')
        ];
        
        foreach ($nameMatches as $name) {
            if (!empty($name)) {
                $paidToName = $name;
                break;
            }
        }

        // 4. Authorized Signatory & Approver Names
        $approverName = DB::table('adm_regist')->where('member_id', $voucher->approved_by)->value('full_name');
        if (!$approverName) {
            // Agar employee nahi hai to User table se admin check karein
            $adminUser = DB::table('users')->where('id', $voucher->approved_by)->first();
            if ($adminUser && $adminUser->email !== 'admin@jankivilla.com') {
                $approverName = $adminUser->name;
            }
        }

        $signatoryName = DB::table('super_admins')->where('ceo_id', $voucher->authorized_signatory)->value('full_name') ?? '';

        // 5. Payment Details formatting
        $paymentRef = '';
        if (strtoupper($voucher->payment_mode) === 'UPI') {
            $paymentRef = $voucher->pay_upi ?? $voucher->transaction_id;
        } else if (in_array(strtoupper($voucher->payment_mode), ['CHEQUE', 'BANK TRANSFER'])) {
            $paymentRef = $voucher->transaction_id;
        }

        // Combine Mode & Type (e.g. Bank Transfer (NEFT))
        $displayMode = $voucher->payment_mode;
        if (!empty($voucher->type) && strtoupper($voucher->payment_mode) === 'BANK TRANSFER') {
            $displayMode .= ' (' . $voucher->type . ')';
        }

        return view('admin.debit_vouchers.print', compact(
            'voucher', 'mode', 'company', 'branch', 
            'formattedDvNo', 'ledgerName', 'paidToName', 
            'approverName', 'signatoryName', 'paymentRef', 'displayMode'
        ));
    }

    // ==========================================
    // 🔥 GET EMPLOYEE ADVANCE HISTORY (For UI Modal)
    // ==========================================
    public function getEmployeeAdvanceHistory(Request $request)
    {
        try {
            // Frontend se hume pura text milega (e.g. "ved prakash - ABDPL-A/0038 [employee]")
            $paidToRaw = $request->q;
            $memberId = null;

            if (preg_match('/-\s*(.*?)\s*\[/', $paidToRaw, $match)) {
                $memberId = trim($match[1]);
            }

            if (!$memberId) {
                return response()->json(['status' => 'error', 'message' => 'Invalid Employee Format']);
            }

            $activeLoan = \App\Models\EmployeeLoan::where('employee_id', $memberId)
                            ->where('status', 'active')
                            ->first();

            if (!$activeLoan) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'has_active' => false,
                        'total_amount' => 0,
                        'paid_amount' => 0,
                        'remaining_amount' => 0,
                        'repayments' => []
                    ]
                ]);
            }

            $repayments = \App\Models\EmployeeLoanRepayment::where('employee_loan_id', $activeLoan->id)
                            ->orderBy('deduction_date', 'desc')
                            ->get()
                            ->map(function($r) {
                                return [
                                    'month' => date('F Y', strtotime($r->salary_month . '-01')),
                                    'date' => date('d-M-Y', strtotime($r->deduction_date)),
                                    'amount' => $r->amount_deducted
                                ];
                            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'has_active' => true,
                    'total_amount' => $activeLoan->total_amount,
                    'paid_amount' => $activeLoan->paid_amount,
                    'remaining_amount' => $activeLoan->remaining_amount,
                    'repayments' => $repayments
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 🔥 GET SALARY DETAILS FOR PAYMENT
    // ==========================================
    public function fetchSalaryDetails(Request $request)
    {
        try {
            $month = $request->month;
            $paidToRaw = $request->employee;
            $memberId = null;

            // Extract member_id using regex (e.g., ABDPL-A/001)
            if (preg_match('/-\s*(.*?)\s*\[/', $paidToRaw, $match)) {
                $memberId = trim($match[1]);
            }

            if (!$memberId) {
                return response()->json(['status' => 'error', 'message' => 'Invalid Employee Format']);
            }

            // Employee table me find karo (adm_regist)
            $employee = \App\Models\Employee::where('member_id', $memberId)->first();

            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee record not found.']);
            }

           // Salary query (Hamesha sabse latest wali uthayega)
            $salary = \App\Models\Salary::where('employee_id', $employee->id)
                        ->where('salary_month', $month)
                        ->whereIn('status', ['pending', 'active']) // 'archived' ignore ho jayega
                        ->orderBy('id', 'desc') // 🔥 Hamesha latest row layega
                        ->first();

            if (!$salary) {
                return response()->json(['status' => 'error', 'message' => 'No pending/active salary found for this month.']);
            }

            // Agar left_amount column null/empty hai to calculate karlo
            $leftAmount = $salary->left_amount ?? ($salary->net_payable_salary - ($salary->paid_amount ?? 0));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'salary_id' => $salary->id,
                    'left_amount' => number_format($leftAmount, 2, '.', '')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


}
