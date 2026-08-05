<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRecord;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{

    // ==========================================
    // HELPER METHOD: Customer Code Generate & Record Sync
    // ==========================================
    private function syncCustomerRecord($customer, $memberId = null)
    {
        if (empty($customer->customer_code)) {
            $company = \App\Models\Company::find($customer->company_id);
            $companyCode = $company ? $company->company_code : 'CMP';

            $randomString = strtoupper(\Illuminate\Support\Str::random(8));
            $generatedCode = "{$companyCode}-CUST/{$randomString}";

            $customer->update(['customer_code' => $generatedCode]);
        }

        $recordExists = \App\Models\CustomerRecord::where('customer_code', $customer->customer_code)->exists();

        if (!$recordExists) {
            \App\Models\CustomerRecord::create([
                'customer_id'   => $customer->customer_id,
                'customer_code' => $customer->customer_code,
                'company_id'    => $customer->company_id,
                'branch_id'     => $customer->branch_id,
                'start_date'    => $customer->registration_date,
                'end_date'      => $customer->d_o_l,
                'member_id'     => $memberId
            ]);
        }
    }


    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;
        $context = $this->getGlobalContext();
        if ($context && $context->is_god) return true;

        $livePerms = self::getLiveActivePermissions($user);
        if ($action === 'add') {
            return in_array("customer_add_direct", $livePerms) || in_array("customer_add_request", $livePerms);
        }
        return in_array("customer_{$action}", $livePerms);
    }

    // ==========================================
    // 1. GET: DAILY CURRENT DATE DATA (customers page)
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$this->checkPermission('view') && !$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $context = $this->getGlobalContext();
        $livePerms = self::getLiveActivePermissions($user);

        // 🔥 Sirf aaj ka data filter karein
        $query = Customer::with(['branch.company'])->whereDate('created_at', now()->toDateString());

        // 🔥 NAYA FIX: Employee & Member combine check (Without relying on brackets)
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee || $context->is_member) {
                // created_by EXACT match ya LIKE match kare, YA phir member_id match kare
                $query->where(function ($q) use ($context) {
                    $q->where('created_by', $context->profile_id)
                        ->orWhere('created_by', 'LIKE', "%{$context->profile_id}%")
                        ->orWhere('member_id', $context->profile_id);
                });
            }
        }

        $totalData = Customer::whereDate('created_at', now()->toDateString())->count();
        $totalFiltered = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);

        $customers = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $customers
        ]);
    }

    // ==========================================
    // 2. SEARCH OLD CUSTOMER
    // ==========================================
    public function searchOldCustomer(Request $request)
    {
        $query = Customer::query();

        if ($request->has('company_id') && !empty($request->company_id)) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('customer_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('customer_code', 'LIKE', "%{$searchTerm}%");
            });
        }

        $customers = $query->orderBy('id', 'desc')->limit(20)->get();

        $customers->transform(function ($customer) {
            $statusCaps = ucfirst($customer->status ?? 'Unknown');
            $customer->display_text = "{$customer->customer_name} ({$customer->customer_id} - {$statusCaps})";
            return $customer;
        });

        return response()->json(['status' => 'success', 'data' => $customers]);
    }

    // ==========================================
    // 3. GENERATE LIVE CREDENTIALS
    // ==========================================
    public function generateCredentials(Request $request)
    {
        $companyId = $request->company_id ?: 1;
        $company = Company::find($companyId);
        $companyPrefix = $company ? $company->company_code : 'CMP';
        $prefix = "{$companyPrefix}-CUST/";

        $lastCustomer = Customer::withTrashed()->where('customer_id', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->first();

        $nextSeq = 1;
        if ($lastCustomer) {
            $parts = explode('/', $lastCustomer->customer_id);
            $nextSeq = (int)end($parts) + 1;
        }

        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        $customerId = "{$prefix}{$sequence}";

        return response()->json(['status' => 'success', 'customer_id' => $customerId]);
    }

    // ==========================================
    // 4. STORE 
    // ==========================================
    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $request->validate([
            'customer_name' => 'required',
            'customer_mobile' => 'required',
            'registration_date' => 'required|date',
        ]);

        $context = $this->getGlobalContext();
        $user = auth()->user();
        $livePerms = self::getLiveActivePermissions($user);

        $data = $request->except(['_token', 'is_old_customer', 'old_customer_code', 'member_id']);

        $data['company_id'] = $request->company_id ?: ($context->company_id ?: ($user->company_id ?? 1));
        $data['branch_id'] = $request->branch_id ?: ($context->branch_id ?: ($user->branch_id ?? null));

        if (!empty($data['branch_id'])) {
            $branch = Branch::with('company')->find($data['branch_id']);
            $data['company_id'] = $branch ? $branch->company_id : $data['company_id'];
        }

        try {
            $customer = DB::transaction(function () use ($data, $request, $context, $livePerms, $user) {

                if ($request->is_old_customer == 'true' || $request->is_old_customer == 1) {
                    $existingCustomer = Customer::where('customer_id', $request->customer_id)->first();

                    if ($existingCustomer) {
                        $existingCustomer->update([
                            'company_id' => $data['company_id'],
                            'branch_id'  => $data['branch_id'],
                            'member_id'  => $request->member_id,
                        ]);

                        if (empty($existingCustomer->customer_code)) {
                            $companyCode = Company::find($data['company_id'])->company_code ?? 'CMP';
                            $randomString = strtoupper(\Illuminate\Support\Str::random(8));
                            $existingCustomer->update(['customer_code' => "{$companyCode}-CUST/{$randomString}"]);
                        }

                        CustomerRecord::create([
                            'customer_id'   => $existingCustomer->customer_id,
                            'customer_code' => $existingCustomer->customer_code,
                            'company_id'    => $existingCustomer->company_id,
                            'branch_id'     => $existingCustomer->branch_id,
                            'start_date'    => $request->registration_date ?? $existingCustomer->registration_date,
                            'end_date'      => $existingCustomer->d_o_l,
                            'member_id'     => $request->member_id
                        ]);
                        return $existingCustomer;
                    }
                }

                $fileFields = ['aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense', 'passport', 'passport_photo', 'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc', 'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport', 'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate', 'nom_pgcertificate', 'nom_otherdoc'];

                foreach ($fileFields as $field) {
                    if ($request->hasFile($field)) {
                        $file = $request->file($field);
                        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('uploads/customers'), $filename);
                        $data[$field] = 'uploads/customers/' . $filename;
                    }
                }

                $data['customer_id'] = $request->customer_id;
                $data['password'] = $request->password;
                $data['member_id'] = $request->member_id;

                // Taki exactly ID store ho jaisa aapke screenshot me hai
                $data['created_by'] = $context->profile_id ?? 'SYS';
                $data['status'] = ($context->is_god || in_array('customer_add_direct', $livePerms)) ? 'active' : 'pending';

                $newCustomer = Customer::create($data);

                $this->syncCustomerRecord($newCustomer, $request->member_id);

                return $newCustomer;
            });

            return response()->json(['status' => 'success', 'message' => "Customer saved successfully! ID: {$customer->customer_id}"]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 5. SHOW
    // ==========================================
    public function show($id)
    {
        $context = $this->getGlobalContext();
        $query = Customer::with(['branch.company']);
        if ($context->is_god) $query->withTrashed();

        $customer = $query->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    // ==========================================
    // 6. UPDATE
    // ==========================================
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $context = $this->getGlobalContext();
        $customer = $context->is_god ? Customer::withTrashed()->findOrFail($id) : Customer::findOrFail($id);

        $data = $request->except(['_token', '_method', 'old_customer_code']);

        // Kisi aur role ke form-tampering se bachne ke liye unset karein
        unset($data['created_by']);

        if (empty($data['password'])) unset($data['password']);

        $fileFields = ['aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense', 'passport', 'passport_photo', 'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc', 'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport', 'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate', 'nom_pgcertificate', 'nom_otherdoc'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer->update($data);
        $this->syncCustomerRecord($customer, $request->member_id ?? $customer->member_id);

        return response()->json(['status' => 'success', 'message' => 'Customer updated successfully']);
    }

    // ==========================================
    // 7. SOFT DELETE & BULK DELETE
    // ==========================================
    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) return response()->json(['message' => 'Unauthorized!'], 403);
        Customer::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Customer moved to trash (Soft Deleted).']);
    }

    public function bulkDelete(Request $request)
    {
        if (!$this->checkPermission('delete')) return response()->json(['message' => 'Unauthorized!'], 403);
        $request->validate(['ids' => 'required|array']);
        Customer::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => count($request->ids) . ' Customers Soft Deleted.']);
    }

    // ==========================================
    // 8. RESTORE 
    // ==========================================
    public function restore($id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god && !$this->checkPermission('restore')) {
            return response()->json(['message' => 'Unauthorized Access!'], 403);
        }

        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return response()->json(['status' => 'success', 'message' => 'Customer Restored Successfully!']);
    }

    // ==========================================
    // 9. UPDATE STATUS
    // ==========================================
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:approve,reject']);
        $customer = Customer::withTrashed()->findOrFail($id);
        $user = auth()->user();

        if ($request->action === 'approve') {
            if (!$this->checkPermission('appr')) return response()->json(['message' => 'Unauthorized'], 403);
            $customer->update(['status' => 'active']);
            $msg = "Customer Approved!";
        } else {
            if (!$this->checkPermission('rej')) return response()->json(['message' => 'Unauthorized'], 403);
            $customer->update(['status' => 'inactive']);
            $msg = "Customer Rejected!";
        }
        return response()->json(['status' => 'success', 'message' => $msg]);
    }

    // ==========================================
    // 10. GET: ALL TIME DIRECTORY DATA (customer_directory page)
    // ==========================================
    public function directory(Request $request)
    {
        $user = auth()->user();
        $livePerms = self::getLiveActivePermissions($user);
        $context = $this->getGlobalContext();

        if (!in_array('cust_dir_view', $livePerms) && !$context->is_god) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $query = Customer::with(['branch.company']);
        if ($context->is_god) {
            $query->withTrashed();
        }

        // 🔥 NAYA FIX: Employee & Member combine check for All Time
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee || $context->is_member) {
                // created_by EXACT match ya LIKE match kare, YA phir member_id match kare
                $query->where(function ($q) use ($context) {
                    $q->where('created_by', $context->profile_id)
                        ->orWhere('created_by', 'LIKE', "%{$context->profile_id}%")
                        ->orWhere('member_id', $context->profile_id);
                });
            }
        }

        $totalData = Customer::count();
        $totalFiltered = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);

        $customers = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $customers
        ]);
    }
}
