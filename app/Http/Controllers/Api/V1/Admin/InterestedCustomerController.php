<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterestedCustomer;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class InterestedCustomerController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        if (!$context) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $role = strtolower($context->role_level);
        $isAdmin = $context->is_god || in_array($role, ['ceo', 'developer', 'admin', 'superadmin', 'manager']);
        $isDirector = $context->is_director;

        // 🔥 NAYA FIX: Frontend se type parameter pakdo (Default: general)
        $type = $request->query('type', 'general');

        // Base query - Active Leads
        $query = InterestedCustomer::with(['branch', 'company'])
            ->where('entry_status', 'active')
            ->orderBy('id', 'desc');

        // Base query - Pending Leads
        $pendingQuery = InterestedCustomer::with(['branch', 'company'])
            ->where('entry_status', 'pending')
            ->orderBy('id', 'desc');

        // 🔥 DYNAMIC FILTER: General vs Interested
        if ($type === 'interested') {
            $query->whereRaw('LOWER(status) != ?', ['general']);
            $pendingQuery->whereRaw('LOWER(status) != ?', ['general']);
        } else {
            $query->whereRaw('LOWER(status) = ?', ['general']);
            $pendingQuery->whereRaw('LOWER(status) = ?', ['general']);
        }

        // Apply Access Filters (RBAC)
        if (!$isAdmin) {
            $query->where('company_id', $context->company_id);
            $pendingQuery->where('company_id', $context->company_id);

            if (!$isDirector) {
                $context->branch_id ? $query->where('branch_id', $context->branch_id) : $query->whereNull('branch_id');
                $query->where(function ($q) use ($context, $user) {
                    $q->where('assigned_telecaller', $context->profile_id)
                        ->orWhere('assigned_telecaller', $user->member_id ?? 'xx')
                        ->orWhereNull('assigned_telecaller');
                });

                if ($context->branch_id) $pendingQuery->where('branch_id', $context->branch_id);
            }
        }

        // ====================================================================
        // SERVER SIDE DATATABLE (Pagination handles 10 at a time)
        // ====================================================================
        if ($request->has('draw')) {
            $dtQuery = clone $query; // Clone base query which already has the status filter

            $totalRecords = $dtQuery->count();
            $filteredRecords = $totalRecords;

            // Handle Search Box
            if ($request->has('search') && !empty($request->search['value'])) {
                $searchValue = strtolower($request->search['value']);
                $dtQuery->where(function ($q) use ($searchValue) {
                    $q->whereRaw('LOWER(cust_name) LIKE ?', ["%{$searchValue}%"])
                        ->orWhere('mobile', 'LIKE', "%{$searchValue}%")
                        ->orWhereRaw('LOWER(assigned_telecaller) LIKE ?', ["%{$searchValue}%"]);
                });
                $filteredRecords = $dtQuery->count();
            }

            $data = $dtQuery->skip($request->start)->take($request->length)->get();

            $formattedData = [];

            // Slugs dynamically set based on type
            $viewSlug = $type === 'interested' ? 'interested_leads_view' : 'general_leads_view';
            $editSlug = $type === 'interested' ? 'interested_leads_edit' : 'general_leads_edit';
            $deleteSlug = $type === 'interested' ? 'interested_leads_delete' : 'general_leads_delete';

            foreach ($data as $d) {
                $compName = $d->company ? $d->company->company_name : '-';
                $bName = $d->branch ? $d->branch->branch_name : 'HO';
                $actions = '
                    <div class="action-btns">
                        <button class="btn btn-sm btn-light text-info view-btn secured-item" data-permission="' . $viewSlug . '" data-id="' . $d->id . '"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="' . $editSlug . '" data-id="' . $d->id . '"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="' . $deleteSlug . '" data-id="' . $d->id . '"><i class="fas fa-trash"></i></button>
                    </div>';

                $badgeColor = $type === 'interested' ? 'bg-primary' : 'bg-secondary';

                $formattedData[] = [
                    "<b>{$compName}</b><br><small class='text-muted'>{$bName}</small>",
                    $d->cust_name,
                    $d->mobile,
                    $d->required_for ?? '-',
                    $d->refer_by ?? '-',
                    $d->assigned_telecaller ?? '-',
                    "<span class='badge {$badgeColor}'>{$d->status}</span>",
                    $actions
                ];
            }

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $formattedData
            ]);
        }

        // ====================================================================
        // INITIAL DATA LOAD (Mobile UI & Base Data)
        // ====================================================================

        $mainData = (clone $query)->take(300)->get();
        $pendingRequests = (clone $pendingQuery)->take(500)->get();

        $staffQuery = DB::table('adm_regist')->select('member_id as staff_id', 'full_name as name', 'role');
        if (!$isAdmin && $context->company_id) {
            $staffQuery->where('company_id', $context->company_id);
        }
        $staffList = $staffQuery->get();

        return response()->json([
            'status'           => 'success',
            'general'          => $mainData, // Frontend dono pages ke liye is array ko use karta hai
            'pending_requests' => $pendingRequests,
            'auth_role'        => $context->role_level,
            'auth_company'     => $context->company_id,
            'auth_branch'      => $context->branch_id,
            'auth_profile_id'  => $context->profile_id,
            'staff_list'       => $staffList,
        ], 200, [], JSON_INVALID_UTF8_IGNORE);
    }

    // ====================================================================
    // FULL EXCEL EXPORT (Handles both General and Interested dynamically)
    // ====================================================================
    public function downloadExport(Request $request)
    {
        if (!auth()->check() && !auth('sanctum')->check()) {
            if ($request->wantsJson() || $request->is('api/*')) return response()->json(['error' => 'Unauthorized'], 401);
            return redirect()->route('admin.login');
        }

        if (!auth()->check() && auth('sanctum')->check()) {
            auth()->setUser(auth('sanctum')->user());
        }

        $context = $this->getGlobalContext();
        $user = auth()->user();
        $type = $request->query('type', 'general');

        $query = \App\Models\InterestedCustomer::with(['company', 'branch'])->where('entry_status', 'active');

        // 🔥 DYNAMIC EXPORT FILTER
        if ($type === 'interested') {
            $query->whereRaw('LOWER(status) != ?', ['general']);
        } else {
            $query->whereRaw('LOWER(status) = ?', ['general']);
        }

        // Role-based filters
        if (!$context->is_god && !in_array(strtolower($context->role_level), ['ceo', 'developer', 'admin', 'superadmin', 'manager'])) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director) {
                $context->branch_id ? $query->where('branch_id', $context->branch_id) : $query->whereNull('branch_id');
                $query->where(function ($q) use ($context, $user) {
                    $q->where('assigned_telecaller', $context->profile_id)
                        ->orWhere('assigned_telecaller', $user->member_id ?? 'xx')
                        ->orWhereNull('assigned_telecaller');
                });
            }
        }

        // Form & Search Filters
        if ($request->filled('from_date') && $request->filled('to_date')) $query->whereBetween('date', [$request->from_date, $request->to_date]);
        if ($request->filled('followup_month')) $query->where('followup_month', $request->followup_month);
        if ($request->filled('refer_by')) $query->where('refer_by', $request->refer_by);
        if ($request->filled('budget_from') && $request->filled('budget_to')) $query->whereBetween('budget', [$request->budget_from, $request->budget_to]);
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('interested_customers');
        $columns = array_values(array_diff($columns, ['id', 'company_id', 'branch_id']));

        $exportData = [];

        $query->chunk(2000, function ($records) use (&$exportData, $columns) {
            foreach ($records as $record) {
                $row = [];
                $row['COMPANY NAME'] = $record->company ? $record->company->company_name : 'N/A';
                $row['BRANCH NAME'] = $record->branch ? $record->branch->branch_name : 'Head Office';

                foreach ($columns as $col) {
                    $row[strtoupper($col)] = $record->{$col};
                }
                $exportData[] = $row;
            }
        });

        return response()->json(['status' => 'success', 'data' => $exportData]);
    }

    // ====================================================================
    // BAAKI SAARE FUNCTIONS SAME RAHENGE (Store, Show, Update, Destroy, etc.)
    // ====================================================================

   public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $isAdmin = $context->is_god || in_array(strtolower($context->role_level), ['ceo', 'developer', 'admin']);

        $rules = ['cust_name' => 'required', 'mobile' => 'required'];
        if ($isAdmin) $rules['company_id'] = 'required|exists:companies,id';

        $request->validate($rules);

        $entryType = $request->input('entry_type', 'direct');
        $data = $request->except(['_token', 'entry_type']);

        if (empty($data['branch_id'])) $data['branch_id'] = null;

        // Company aur Branch ID set karna
        if (!$isAdmin && !$context->is_director) {
            // Employee ke liye
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id;
            $data['assigned_telecaller'] = $context->profile_id;
        } else {
            // Admin/Director ke liye
            $data['company_id'] = $context->company_id;
        }

        // 🔥 FIX: Ab dono ke liye check hoga ki button kaun sa daba tha!
        // Agar "Request Lead" (request) daba tha toh 'pending', warna 'active'
        $data['entry_status'] = ($entryType === 'request') ? 'pending' : 'active';

        InterestedCustomer::create($data);
        return response()->json(['status' => 'success', 'message' => 'Lead processed successfully']);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $customer = InterestedCustomer::with(['branch', 'company'])->find($id);

        if (!$customer) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        if ($context->is_director && $customer->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $customer = InterestedCustomer::findOrFail($id);

        if ($context->is_director && $customer->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Action'], 403);
        }

        $data = $request->except(['_token', 'entry_status']);
        if (empty($data['branch_id'])) $data['branch_id'] = null;

        $customer->update($data);
        return response()->json(['status' => 'success', 'message' => 'Customer Updated Successfully']);
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $customer = InterestedCustomer::findOrFail($id);

        if ($context->is_director && $customer->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Action'], 403);
        }

        $customer->delete();
        return response()->json(['status' => 'success', 'message' => 'Customer Deleted Successfully']);
    }

    public function updateEntryStatus(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $isAdmin = $context->is_god || in_array(strtolower($context->role_level), ['ceo', 'developer']);

        if (!$isAdmin && !$context->is_director) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Action'], 403);
        }

        $request->validate(['entry_status' => 'required|in:active,inactive']);
        $customer = InterestedCustomer::findOrFail($id);

        if ($context->is_director && $customer->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope'], 403);
        }

        $customer->update(['entry_status' => $request->entry_status]);
        $statusMsg = $request->entry_status == 'active' ? 'Approved' : 'Rejected';

        return response()->json(['status' => 'success', 'message' => "Lead Request $statusMsg Successfully."]);
    }

    public function assignTelecaller(Request $request)
    {
        $context = $this->getGlobalContext();
        $isAdmin = $context->is_god || in_array(strtolower($context->role_level), ['ceo', 'developer']);

        if (!$isAdmin && !$context->is_director) {
            return response()->json(['status' => false, 'message' => 'Unauthorized Action']);
        }

        $telecaller = $request->telecaller;
        $status = $request->status ?? 'General';
        $dataFrom = (int)$request->data_from;
        $dataTo = (int)$request->data_to;

        $query = InterestedCustomer::where('status', $status)->where('entry_status', 'active');
        if ($context->is_director) $query->where('company_id', $context->company_id);

        $records = $query->skip($dataFrom - 1)->take(($dataTo - $dataFrom) + 1)->get();

        if ($records->isEmpty()) return response()->json(['status' => false, 'message' => "No records found in range."]);

        foreach ($records as $record) {
            $record->update(['assigned_telecaller' => $telecaller]);
        }

        return response()->json(['status' => true, 'message' => "Successfully Assigned to $telecaller"]);
    }

    public function filterReports(Request $request)
    {
        $context = $this->getGlobalContext();
        $isAdmin = $context->is_god || in_array(strtolower($context->role_level), ['ceo', 'developer']);

        $query = InterestedCustomer::with(['branch', 'company'])->where('entry_status', 'active');

        if (!$isAdmin && !$context->is_director) {
            $query->where('company_id', $context->company_id)
                ->where('branch_id', $context->branch_id)
                ->where(function ($q) use ($context) {
                    $q->where('assigned_telecaller', $context->profile_id)
                        ->orWhereNull('assigned_telecaller')
                        ->orWhere('assigned_telecaller', '');
                });
        } elseif ($context->is_director) {
            $query->where('company_id', $context->company_id);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        }
        if ($request->filled('followup_month')) $query->where('followup_month', $request->followup_month);
        if ($request->filled('refer_by')) $query->where('refer_by', $request->refer_by);
        if ($request->filled('budget_from') && $request->filled('budget_to')) {
            $query->whereBetween('budget', [$request->budget_from, $request->budget_to]);
        }

        $data = $query->get();
        if ($data->isEmpty()) return response()->json(['status' => false, 'message' => "No records found."]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function import(Request $request)
    {
        $leads = $request->input('leads');
        $inserts = [];

        foreach ($leads as $row) {
            if (empty($row['cust_name']) || empty($row['mobile'])) continue;

            $sanitizeDate = function ($val) {
                if (!$val || $val == '-') return null;
                try {
                    return date('Y-m-d', strtotime($val));
                } catch (\Exception $e) {
                    return null;
                }
            };

            $inserts[] = [
                'company_id'          => 1,
                'branch_id'           => null,
                'entry_status'        => 'active',
                'cust_name'           => $row['cust_name'],
                'mobile'              => $row['mobile'],
                'email'               => $row['email'],
                'budget'              => $row['budget'],
                'assigned_telecaller' => $row['assigned_telecaller'],
                'reference'           => $row['reference'],
                'refer_by'            => $row['refer_by'],
                'alternate_no'        => $row['alternate_no'],
                'address'             => $row['address'],
                'date'                => $sanitizeDate($row['date']),
                'interested_for'      => $row['interested_for'],
                'required_for'        => $row['required_for'],
                // Import karte time status waise ka waisa jayega jaisa backend JSON me set h
                'status'              => $row['status'] ?? 'General',
                'followup_date'       => $sanitizeDate($row['followup_date']),
                'followup_month'      => $row['followup_month'],
                'remark'              => $row['remark'],
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        if (!empty($inserts)) InterestedCustomer::insert($inserts);
        return response()->json(['status' => 'success', 'message' => 'Imported successfully']);
    }
}
