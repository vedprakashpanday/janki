<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
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
    // 1. GET: Server-Side Data Load
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$this->checkPermission('view') && !$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $query = Customer::with(['branch.company']);
        $context = $this->getGlobalContext();
        $livePerms = self::getLiveActivePermissions($user);

        // 🔥 FILTER FIX: Ab 'LIKE' use hoga kyunki 'created_by' mein Name aur ID dono hain 🔥
        if (!$context->is_god && !$context->is_director) {
            $query->where('branch_id', $context->branch_id)
                ->where('created_by', 'LIKE', '%' . $context->profile_id . '%');
        } elseif ($context->is_director) {
            $query->where('company_id', $context->company_id);
        }

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_id', 'LIKE', "%{$search}%")
                    ->orWhere('customer_mobile', 'LIKE', "%{$search}%")
                    ->orWhere('created_by', 'LIKE', "%{$search}%");
            });
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
            "data" => $customers,
            "auth_context" => $context,
            "auth_perms" => $livePerms
        ]);
    }

    // ==========================================
    // 2. STORE (WITH CONCURRENCY & FORMATTED CREATOR)
    // ==========================================
    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $request->validate([
            'customer_name' => 'required',
            'customer_mobile' => 'required',
            'booking_date' => 'required|date',
        ]);

        $context = $this->getGlobalContext();
        $user = auth()->user();
        $livePerms = self::getLiveActivePermissions($user);
        $data = $request->except(['_token']);

        $branch = null;
        $data['company_id'] = $request->company_id ?: ($context->company_id ?: $user->company_id);
        $data['branch_id'] = $request->branch_id ?: ($context->branch_id ?: $user->branch_id);

        if (!empty($data['branch_id'])) {
            $branch = Branch::with('company')->find($data['branch_id']);
            if ($branch) {
                $data['company_id'] = $branch->company_id;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid Branch Selection!'], 400);
            }
        }

        if (empty($data['company_id'])) {
            $fallback = Company::first();
            $data['company_id'] = $fallback ? $fallback->id : 1;
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

        try {
            $customer = DB::transaction(function () use ($data, $request, $context, $livePerms, $user) {

                $lockedCompany = Company::where('id', $data['company_id'])->lockForUpdate()->first();

                // 🔥 FORMAT FIXED: "Employee Name (EMP001)" 🔥
                $empName = $user->employee_name ?? $user->full_name ?? $user->name ?? 'Unknown User';
                $empId = $context->profile_id ?? 'SYS';
                $data['created_by'] = "{$empName} ({$empId})";

                if ($context->is_god || in_array('customer_add_direct', $livePerms)) {
                    $data['status'] = 'active';
                } else {
                    $data['status'] = 'pending';
                }

                $companyPrefix = $lockedCompany ? $lockedCompany->company_code : 'CMP';
                $prefix = "{$companyPrefix}-CUST/";

                $existingIds = Customer::where('customer_id', 'LIKE', $prefix . '%')->pluck('customer_id');
                $maxSeq = 0;
                foreach ($existingIds as $cId) {
                    $parts = explode('/', $cId);
                    $seqStr = end($parts);
                    $seqInt = (int)$seqStr;
                    if ($seqInt > $maxSeq) {
                        $maxSeq = $seqInt;
                    }
                }

                $nextSeq = $maxSeq + 1;
                $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
                $data['customer_id'] = "{$prefix}{$sequence}";

                $firstName = explode(' ', $request->customer_name)[0];
                $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
                $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
                $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

                return Customer::create($data);
            }, 5);

            return response()->json(['status' => 'success', 'message' => "Customer saved! ID: {$customer->customer_id}"]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'System busy due to high concurrency. Please click Save again!'], 500);
        }
    }

    // ==========================================
    // 3. SHOW
    // ==========================================
    public function show($id)
    {
        if (!$this->checkPermission('view') && !$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $customer = Customer::with(['branch.company'])->findOrFail($id);
        $context = $this->getGlobalContext();

        if (!$context->is_god) {
            if ($context->is_director && $customer->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Scope Error!'], 403);
            } elseif (!$context->is_director) {
                // 🔥 str_contains logic for scope check since it's a concatenated string 🔥
                if ($customer->branch_id != $context->branch_id || !str_contains((string)$customer->created_by, (string)$context->profile_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Scope Error! You can only view your own entries.'], 403);
                }
            }
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    // ==========================================
    // 4. UPDATE
    // ==========================================
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $customer = Customer::findOrFail($id);
        $context = $this->getGlobalContext();

        if (!$context->is_god) {
            if ($context->is_director && $customer->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Scope Error!'], 403);
            } elseif (!$context->is_director) {
                if ($customer->branch_id != $context->branch_id || !str_contains((string)$customer->created_by, (string)$context->profile_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Scope Error! You can only edit your own entries.'], 403);
                }
            }
        }

        $data = $request->except(['_token', 'customer_id', '_method', 'created_by']);

        $branch = null;
        $user = auth()->user();

        $data['company_id'] = $request->company_id ?: ($context->company_id ?: $user->company_id);
        $data['branch_id'] = $request->branch_id ?: ($context->branch_id ?: $user->branch_id);

        if (!empty($data['branch_id'])) {
            $branch = Branch::with('company')->find($data['branch_id']);
            if ($branch) {
                $data['company_id'] = $branch->company_id;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid Branch Selection!'], 400);
            }
        }

        if (empty($data['company_id'])) {
            $fallback = Company::first();
            $data['company_id'] = $fallback ? $fallback->id : 1;
        }

        if ($request->has('status')) {
            $data['status'] = $request->status;
        }

        if (empty($data['password'])) unset($data['password']);

        $fileFields = ['aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense', 'passport', 'passport_photo', 'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc', 'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport', 'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate', 'nom_pgcertificate', 'nom_otherdoc'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer->update($data);
        return response()->json(['status' => 'success', 'message' => 'Customer updated successfully']);
    }

    // ==========================================
    // 5. DESTROY
    // ==========================================
    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $customer = Customer::findOrFail($id);
        $context = $this->getGlobalContext();

        if (!$context->is_god) {
            if ($context->is_director && $customer->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Scope Error!'], 403);
            } elseif (!$context->is_director) {
                if ($customer->branch_id != $context->branch_id || !str_contains((string)$customer->created_by, (string)$context->profile_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Scope Error! You can only delete your own entries.'], 403);
                }
            }
        }

        $customer->delete();
        return response()->json(['status' => 'success', 'message' => 'Customer deleted successfully']);
    }
}
