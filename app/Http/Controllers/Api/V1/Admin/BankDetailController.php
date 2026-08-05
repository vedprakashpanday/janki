<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\BankDetail;
use App\Http\Resources\BankDetailResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;



;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankDetailController extends Controller
{
    // 1. Sare data list karne ke liye
    public function index()
    {
        $bankDetails = BankDetail::orderBy('id', 'desc')->get();
        return BankDetailResource::collection($bankDetails);
    }

 // 1. Daily View (Sirf Aaj ka aur apna data)
    public function getDailyData(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $query = BankDetail::select('tbl_bank_details.*', 'companies.company_name', 'branches.branch_name')
            ->leftJoin('companies', 'tbl_bank_details.company_id', '=', 'companies.id')
            ->leftJoin('branches', 'tbl_bank_details.branch_id', '=', 'branches.id')
            ->whereDate('tbl_bank_details.created_at', Carbon::today());

        // 🔥 FIX: Admin/CEO Bypass - normal user ko sirf apni entry dikhegi
        if (!$context->is_god && $context->role_level !== 'ceo') {
            $query->where('tbl_bank_details.created_by', $context->profile_id);
        }

        return response()->json($query->orderBy('tbl_bank_details.id', 'desc')->get());
    }

   // 2. Directory View (All time data with Filters & Ownership)
    public function getDirectoryData(Request $request)
    {
        $context = $this->getGlobalContext();

        $query = BankDetail::select('tbl_bank_details.*', 'companies.company_name', 'branches.branch_name')
            ->leftJoin('companies', 'tbl_bank_details.company_id', '=', 'companies.id')
            ->leftJoin('branches', 'tbl_bank_details.branch_id', '=', 'branches.id')
            ->orderBy('tbl_bank_details.id', 'desc');

        // 🔥 FIX: Admin/CEO Bypass - normal user ko sirf apni entry dikhegi
        if (!$context->is_god && $context->role_level !== 'ceo') {
            $query->where('tbl_bank_details.created_by', $context->profile_id);
        }

        // Apply Filters
        if ($request->filled('company_id')) {
            $query->where('tbl_bank_details.company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('tbl_bank_details.branch_id', $request->branch_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tbl_bank_details.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        return response()->json($query->get());
    }

  // 3. Search Account Holder (CRASH-PROOF & Company/Branch Filtered)
    public function searchAccountHolder(Request $request)
    {
        $search = $request->input('q');
        $companyId = $request->input('company_id');
        $branchId = $request->input('branch_id'); // Head Office me empty aayega

        // Agar company select nahi ki hai ya 3 letter se kam type kiya hai toh blank return karo
        if (strlen($search) < 3 || empty($companyId)) {
            return response()->json([]);
        }

        $q = "%{$search}%";
        $results = collect([]);

        // 🔥 HELPER FUNCTION: Jo query fail hone par crash nahi hogi
        $fetchSafe = function ($tableName, $idCol, $nameCol, $typeStr, $checkBranch = true) use ($q, $companyId, $branchId) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) return [];

                $query = DB::table($tableName)
                    ->select("$idCol as id", "$nameCol as name", DB::raw("'$typeStr' as type"))
                    ->where(function ($w) use ($q, $idCol, $nameCol) {
                        $w->where($nameCol, 'LIKE', $q)->orWhere($idCol, 'LIKE', $q);
                    })
                    ->where('company_id', $companyId);

                // Branch filter check
                if ($checkBranch) {
                    if (!empty($branchId)) {
                        $query->where('branch_id', $branchId);
                    } else {
                        // Head Office Logic (NULL, empty string, ya 0)
                        $query->where(function ($w) {
                            $w->whereNull('branch_id')->orWhere('branch_id', '')->orWhere('branch_id', 0);
                        });
                    }
                }

                return $query->limit(5)->get();
            } catch (\Exception $e) {
                return []; 
            }
        };

        // 1. In tables mein Company ID aur Branch ID dono check hogi (ab directors bhi isme aa gaya)
        $results = $results->merge($fetchSafe('customers', 'customer_id', 'customer_name', 'Customer', true));
        $results = $results->merge($fetchSafe('members', 'member_id', 'member_name', 'Member', true));
        $results = $results->merge($fetchSafe('adm_regist', 'member_id', 'full_name', 'ADM', true));
        $results = $results->merge($fetchSafe('agents', 'agent_id', 'full_name', 'Agent', true));
        $results = $results->merge($fetchSafe('vendors', 'vendor_id', 'full_name', 'Vendor', true));
        
        // 🔥 NAYA: Directors ab Branch wise check hoga
        $results = $results->merge($fetchSafe('directors', 'director_id', 'full_name', 'Director', true));

        // Landowner Table Safety
        $landTable = \Illuminate\Support\Facades\Schema::hasTable('tbl_landowner') ? 'tbl_landowner' : (\Illuminate\Support\Facades\Schema::hasTable('landowners') ? 'landowners' : null);
        if ($landTable) {
            $results = $results->merge($fetchSafe($landTable, 'land_owner_id', 'land_owner_name', 'Landowner', true));
        }

        // 2. In tables mein SIRF Company ID check hogi (Branch Bypass)
        // Super Admins me kewal company_id hai branch_id nahi, isliye ye false rahega
        $results = $results->merge($fetchSafe('super_admins', 'ceo_id', 'full_name', 'Super Admin', false));

        // Sabko combine karke max 20 results bhejo
        return response()->json($results->take(20));
    }

    // 4. Data Update (Edit Modal Se)
    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $bankDetail = BankDetail::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'member_id' => 'required|string',
            'account_name' => 'required|string',
            // Unique rule ko is id ke liye ignore karna zaroori hai warna "Account already exists" aayega
            'account_no' => [
                'required',
                'string',
                Rule::unique('tbl_bank_details', 'account_no')->ignore($bankDetail->id),
            ],
            'account_type' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'ifsc_code' => 'nullable|string',
        ]);

        // Khali values (Head Office) ko database me correctly NULL bhejenge
        $validated['company_id'] = $request->company_id ?: 1;
        $validated['branch_id'] = $request->branch_id ?: null;

        // Normal users agar edit karein aur permission strictly add request wali ho to status 'pending' me jaa sakta hai (Optional Logic)
        // Yahan currently normal update perform ho raha hai
        $bankDetail->update($validated);

        return response()->json([
            'message' => 'Bank details updated successfully', 
            'data' => $bankDetail
        ], 200);
    }

  public function printPreview(Request $request)
    {
        $query = BankDetail::orderBy('id', 'desc');
        
        $company = null;
        $branch = null;

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
            $company = \App\Models\Company::find($request->company_id);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
            $branch = \App\Models\Branch::find($request->branch_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $bankDetails = $query->get();

        // Agar filter me company select nahi ki gayi hai, to default user ki company bhejenge
        if (!$company) {
            $user = auth()->user();
            $company = \App\Models\Company::find($user->company_id ?? 1);
        }

        return view('bank-details.print', compact('bankDetails', 'company', 'branch'));
    }

    // 3. Store Data (Add Direct or Add Request)
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $permissions = $context->permissions ?? [];

        $validated = $request->validate([
            'company_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'member_id' => 'required|string',
            'account_name' => 'required|string',
            'account_no' => 'required|string|unique:tbl_bank_details',
            'account_type' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'ifsc_code' => 'nullable|string',
        ]);

        // Default Values
        $validated['company_id'] = $request->company_id ?? 1;
        $validated['branch_id'] = $request->branch_id ?? null;
        $validated['created_by'] = $context->profile_id;

        // Status Logic based on Permission or Bypass
        if ($context->is_god || in_array('bank_add_direct', $permissions) || in_array('bank_dir_add_direct', $permissions)) {
            $validated['status'] = 'active';
        } elseif (in_array('bank_add_request', $permissions) || in_array('bank_dir_add_request', $permissions)) {
            $validated['status'] = 'pending';
        } else {
            return response()->json(['message' => 'Unauthorized to add bank details'], 403);
        }

        $bankDetail = BankDetail::create($validated);
        return response()->json(['message' => 'Bank details saved successfully', 'data' => $bankDetail], 201);
    }

    // 3. Single record dekhne ke liye
    public function show($id)
    {
        $bankDetail = BankDetail::findOrFail($id);
        return new BankDetailResource($bankDetail);
    }

   // 4. Status Update (Approve / Reject)
    public function updateStatus(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $bankDetail = BankDetail::findOrFail($id);
        
        $action = $request->action; // 'approve' or 'reject'
        
        if ($action === 'approve') {
            $bankDetail->status = 'active';
        } elseif ($action === 'reject') {
            $bankDetail->status = 'inactive';
        }

        $bankDetail->save();
        return response()->json(['message' => "Status updated to {$bankDetail->status}"]);
    }

    // 5. Data delete karne ke liye
    public function destroy($id)
    {
        $bankDetail = BankDetail::findOrFail($id);
        $bankDetail->delete();

        return response()->json([
            'message' => 'Bank details successfully deleted'
        ], 20);
    }

  
    // 2. Company Live Search
    public function searchCompany(Request $request)
    {
        $search = $request->input('q');
        $companies = Company::where('status', 'active')
            ->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('company_code', 'LIKE', "%{$search}%");
            })->limit(10)->get(['id', 'company_name']);

        return response()->json($companies);
    }

    // 3. Branch Live Search (Based on Company)
    public function searchBranch(Request $request)
    {
        $search = $request->input('q');
        $companyId = $request->input('company_id');

        $query = Branch::where('branch_status', 'active');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search) {
            $query->where('branch_name', 'LIKE', "%{$search}%");
        }

        $branches = $query->limit(10)->get(['id', 'branch_name']);
        return response()->json($branches);
    }
}