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

        $type = $request->query('type', 'general');

        // 🔥 VIEW PERMISSION CHECK
        $viewSlug = $type === 'interested' ? 'interested_leads_view' : 'general_leads_view';
        $hasView = $isAdmin || in_array($viewSlug, $context->permissions ?? []);

        // Agar view permission nahi hai aur admin bhi nahi hai, to table ke liye khali data bhejein
        if (!$hasView && !$isAdmin) {
            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        // Base query - Active Leads
        $query = InterestedCustomer::with(['branch', 'company'])
            ->where('entry_status', 'active')
            ->orderBy('id', 'desc');

        // Base query - Pending Leads
        $pendingQuery = InterestedCustomer::with(['branch', 'company'])
            ->where('entry_status', 'pending')
            ->orderBy('id', 'desc');

        // DYNAMIC FILTER: General vs Interested
        if ($type === 'interested') {
            $query->whereRaw('LOWER(status) != ?', ['general']);
            $pendingQuery->whereRaw('LOWER(status) != ?', ['general']);
        } else {
            $query->whereRaw('LOWER(status) = ?', ['general']);
            $pendingQuery->whereRaw('LOWER(status) = ?', ['general']);
        }

        // ====================================================================
        // 1. MAIN DATA ACCESS FILTER (ALL TIME DATA)
        // ====================================================================
        if (!$isAdmin) {
            $query->where('company_id', $context->company_id);
            $pendingQuery->where('company_id', $context->company_id);

            if (!$isDirector) {
                $context->branch_id ? $query->where('branch_id', $context->branch_id) : $query->whereNull('branch_id');
                
                // 🔥 NAYA LOGIC: assigned_telecaller ya called_by me user ki member_id ya name ho
                $userIdentifier = $user->member_id ?? $context->profile_id;
                $userName = $user->full_name ?? $user->name ?? $user->member_name ?? '';

                $query->where(function($q) use ($userIdentifier, $userName) {
                    $q->where('assigned_telecaller', $userIdentifier)
                      ->orWhere('called_by', $userIdentifier)
                      ->orWhere('assigned_telecaller', $userName)
                      ->orWhere('called_by', $userName);
                });

                if ($context->branch_id) {
                    $pendingQuery->where('branch_id', $context->branch_id);
                }
                
                $pendingQuery->where(function($q) use ($userIdentifier, $userName) {
                    $q->where('assigned_telecaller', $userIdentifier)
                      ->orWhere('called_by', $userIdentifier)
                      ->orWhere('assigned_telecaller', $userName)
                      ->orWhere('called_by', $userName);
                });
            }
        }

        // ====================================================================
        // SERVER SIDE DATATABLE 
        // ====================================================================
        if ($request->has('draw')) {
            $dtQuery = clone $query; 

            $totalRecords = $dtQuery->count();
            $filteredRecords = $totalRecords;

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

            $viewSlugBtn = $type === 'interested' ? 'interested_leads_view' : 'general_leads_view';
            $editSlugBtn = $type === 'interested' ? 'interested_leads_edit' : 'general_leads_edit';
            $deleteSlugBtn = $type === 'interested' ? 'interested_leads_delete' : 'general_leads_delete';

            foreach ($data as $d) {
                $compName = $d->company ? $d->company->company_name : '-';
                $bName = $d->branch ? $d->branch->branch_name : 'HO';
                $actions = '
                    <div class="action-btns">
                        <button class="btn btn-sm btn-light text-info view-btn secured-item" data-permission="' . $viewSlugBtn . '" data-id="' . $d->id . '"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="' . $editSlugBtn . '" data-id="' . $d->id . '"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="' . $deleteSlugBtn . '" data-id="' . $d->id . '"><i class="fas fa-trash"></i></button>
                    </div>';

                $badgeColor = $type === 'interested' ? 'bg-primary' : 'bg-secondary';

                $formattedData[] = [
                    '<input type="checkbox" class="form-check-input row-checkbox border-dark" value="' . $d->id . '" style="transform: scale(1.2);">',
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

        // INITIAL DATA LOAD (Mobile UI & Base Data)
        $mainData = (clone $query)->take(300)->get();
        $pendingRequests = (clone $pendingQuery)->take(500)->get();

        $staffQuery = DB::table('adm_regist')->select('member_id as staff_id', 'full_name as name', 'role','emp_status');
        if (!$isAdmin && $context->company_id) {
            $staffQuery->where('company_id', $context->company_id);
        }
        $staffList = $staffQuery->get();

        // 🔥 NAYA: Custom 'refer_by' list nikalne ke liye (Case-insensitive unique)
        $rawRefers = \App\Models\InterestedCustomer::whereNotNull('refer_by')
            ->where('refer_by', '!=', '')
            ->distinct()
            ->pluck('refer_by')
            ->toArray();

        $uniqueRefers = [];
        $seen = [];
        $staffIds = $staffList->pluck('staff_id')->map(function($id) { return strtolower(trim($id)); })->toArray();

        foreach ($rawRefers as $ref) {
            $clean = trim($ref);
            $lower = strtolower($clean); // "JUST DAIL" aur "just dail" ko ek manega
            
            // Check karein ki ye naam pehle na aaya ho aur kisi Employee ki ID na ho
            if (!empty($lower) && !in_array($lower, $seen) && !in_array($lower, $staffIds)) {
                $seen[] = $lower;
                $uniqueRefers[] = $clean; // Original format me save karega, jaise "Just Dail"
            }
        }

        $todayQuery = \App\Models\InterestedCustomer::where('entry_status', 'active')
                                    ->whereDate('created_at', now()->toDateString());
        
        if ($type === 'interested') {
            $todayQuery->whereRaw('LOWER(status) != ?', ['general']);
        } else {
            $todayQuery->whereRaw('LOWER(status) = ?', ['general']);
        }

        // 2. TODAY COUNT ACCESS FILTER
        if (!$isAdmin) {
            $todayQuery->where('company_id', $context->company_id);
            if (!$isDirector) {
                $userIdentifier = $user->member_id ?? $context->profile_id;
                $userName = $user->full_name ?? $user->name ?? $user->member_name ?? '';
                $todayQuery->where(function($q) use ($userIdentifier, $userName) {
                    $q->where('assigned_telecaller', $userIdentifier)
                      ->orWhere('called_by', $userIdentifier)
                      ->orWhere('assigned_telecaller', $userName)
                      ->orWhere('called_by', $userName);
                });
            }
        }
        $todayCount = $todayQuery->count();

   return response()->json([
            'status'           => 'success',
            'general'          => $mainData, 
            'pending_requests' => $pendingRequests,
            'auth_role'        => $context->role_level,
            'auth_company'     => $context->company_id,
            'auth_branch'      => $context->branch_id,
            'auth_profile_id'  => $context->profile_id,
            'staff_list'       => $staffList,
            'custom_refers'    => $uniqueRefers, // 🔥 YE LINE ADD KAREN
            'today_count'      => $todayCount,
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

        // 🔥 FIX: Company_id Overwrite Bug
        if (!$isAdmin && !$context->is_director) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id;
            // Agar employee ne manual select nahi kiya hai, to by default uski ID dal do
            if(empty($data['assigned_telecaller'])) {
                $data['assigned_telecaller'] = $context->profile_id;
            }
        } else {
            // Admin/Director ne form me jo company chuni hai, wahi use hogi
            $data['company_id'] = $request->company_id ?? $context->company_id;
        }

        $data['entry_status'] = ($entryType === 'request') ? 'pending' : 'active';


   // 🔥 YAHAN NAYA CODE DAALNA HAI: Duplicate Entry Check
    $isDuplicate = \App\Models\InterestedCustomer::where('assigned_telecaller', $request->assigned_telecaller)
        ->where('cust_name', $request->cust_name)
        ->where('mobile', $request->mobile)
        ->where('status', $request->status)
        ->exists();

    if ($isDuplicate) {
        return response()->json([
            'success' => false,
            'is_duplicate' => true,
            'message' => 'Ye entry pehle se maujood hai! (Name, Mobile, Telecaller & Status same hai)'
        ]);
    }
    // 🔥 DUPLICATE CHECK END


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

       // Existing code me jahan data le rahe hain, wahan 'entry_type' add kar dein
        $data = $request->except(['_token', '_method', 'entry_type']);
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
        $request->validate([
            'id_from' => 'required|integer',
            'id_to' => 'required|integer',
            'telecaller_id' => 'required|string',
        ]);

        $id_from = $request->id_from;
        $id_to = $request->id_to;
        $telecaller_id = $request->telecaller_id;
        
        // 🔥 FIX: JS se aane wale true/false ko safely handle karne ke liye filter_var lagaya
        $force_assign = filter_var($request->input('force_assign', false), FILTER_VALIDATE_BOOLEAN);

        $alreadyAssigned = \App\Models\InterestedCustomer::whereBetween('id', [$id_from, $id_to])
            ->whereNotNull('called_by')
            ->where('called_by', '!=', '')
            ->pluck('id')
            ->toArray();

        // Agar conflict hai aur user ne force_assign (overwrite) permission nahi di hai
        if (count($alreadyAssigned) > 0 && !$force_assign) {
            return response()->json([
                'status' => 'conflict',
                'message' => count($alreadyAssigned) . ' leads pehle se assign hain.',
                'assigned_count' => count($alreadyAssigned),
                'assigned_ids' => $alreadyAssigned
            ]);
        }

        // Agar pehle se assign nahi hai, YA FIR SweetAlert par "Haan" click kar diya ho
        \App\Models\InterestedCustomer::whereBetween('id', [$id_from, $id_to])
            ->update([
                'called_by' => $telecaller_id,
                'assigned_telecaller' => $telecaller_id 
            ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Telecaller successfully assign ho gaya hai!'
        ]);
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
        $leads = $request->input('leads', []);
        
        if (empty($leads)) {
            return response()->json(['status' => 'success', 'inserted' => 0, 'db_duplicates' => 0]);
        }

        // 1. Chunk me aaye hue saare mobile numbers ek array me nikal lein
        $mobiles = array_column($leads, 'mobile');

        // 2. Database se ek hi baar me check karein ki inme se kaun se numbers pehle se hain
        $existingMobiles = \App\Models\InterestedCustomer::whereIn('mobile', $mobiles)
            ->pluck('mobile')
            ->toArray();

        $existingMobiles = array_map('strval', $existingMobiles); // Comparison ke liye string me badle

        $inserts = [];
        $dbDuplicatesCount = 0;
        $now = now();

        // 3. Data filter karein
        foreach ($leads as $row) {
            $mobile = (string) $row['mobile'];
            
            // Agar database me pehle se hai, toh skip karein
            if (in_array($mobile, $existingMobiles)) {
                $dbDuplicatesCount++;
                continue;
            }

            // Agar galti se isi array me double number aa gaya ho, usko bhi rokein
            if (isset($inserts[$mobile])) {
                continue;
            }

            $inserts[$mobile] = [
                'company_id'          => 1,
                'branch_id'           => null,
                'entry_status'        => 'active',
                'cust_name'           => $row['cust_name'] ?? 'Unknown',
                'mobile'              => $mobile,
                'email'               => $row['email'] ?? null,
                'address'             => $row['address'] ?? null,
                'remark'              => $row['remark'] ?? 'RAW DATA',
                'status'              => $row['status'] ?? 'General',
                'assigned_telecaller' => $row['assigned_telecaller'] ?? null,
                'reference'           => $row['reference'] ?? null,
                'refer_by'            => $row['refer_by'] ?? null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        $insertedCount = count($inserts);

        // 4. Bacha hua fresh data ek hi baar me database me daal dein
        if ($insertedCount > 0) {
            \App\Models\InterestedCustomer::insert(array_values($inserts));
        }

        return response()->json([
            'status' => 'success',
            'inserted' => $insertedCount,
            'db_duplicates' => $dbDuplicatesCount
        ]);
    }

    public function bulkDelete(Request $request)
{
    if (!$request->has('ids') || empty($request->ids)) {
        return response()->json(['success' => false, 'message' => 'No records selected!']);
    }
    
    // Yahan soft-delete ya permanent delete jo bhi model me set hai, wo ho jayega
    \App\Models\InterestedCustomer::whereIn('id', $request->ids)->delete();
    
    return response()->json(['success' => true, 'message' => 'Selected records deleted successfully.']);
}

public function checkMobile(Request $request)
{
    $query = \App\Models\InterestedCustomer::where('mobile', $request->mobile);
    
    // Agar hum edit kar rahe hain, toh same record ko duplicate na mane isliye exclude karenge
    if ($request->has('exclude_id') && !empty($request->exclude_id)) {
        $query->where('id', '!=', $request->exclude_id);
    }
    
    $exists = $query->exists();
    
    return response()->json(['exists' => $exists]);
}

// 🔥 UPDATED: adm_regist se data uthana
    public function getReportEmployees(Request $request)
    {
        $branches = $request->branches ?? [];
        $depts = $request->depts ?? [];
        
        // Seedha adm_regist table se query
        $query = \Illuminate\Support\Facades\DB::table('adm_regist');
        
        if (!empty($branches)) {
            // Agar Head Office (empty string) selected hai
            if (in_array("", $branches)) {
                $query->where(function($q) use ($branches) {
                    $q->whereIn('branch_id', array_filter($branches))
                      ->orWhereNull('branch_id')
                      ->orWhere('branch_id', ''); // Safe check
                });
            } else {
                $query->whereIn('branch_id', $branches);
            }
        }
        
        if (!empty($depts)) {
            $query->whereIn('department_id', $depts);
        }
        
        // Sirf wahi 3 columns uthayenge jo aapko chahiye
        $emps = $query->select('id', 'member_id', 'full_name')->get();
        
        return response()->json(['success' => true, 'data' => $emps,'departments'=>$depts]);
    }

    // 🔥 NAYA CODE: Report Data Count Generate Karna
    public function generatePerformanceReport(Request $request)
    {
        $emps = $request->employees ?? []; // Ye 'ABDPL-A/0022' jaisa array hoga
        $from = $request->from_date;
        $to = $request->to_date;

        $query = \App\Models\InterestedCustomer::query();

        // Date Range Filter
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to) $query->whereDate('created_at', '<=', $to);
        
        // Employee Filter
        if (!empty($emps)) {
            $query->whereIn('assigned_telecaller', $emps);
        }

        // Grouping karke Entries Count nikalna
        $report = $query->select('assigned_telecaller', DB::raw('count(*) as total'))
                        ->groupBy('assigned_telecaller')
                        ->get();

        return response()->json(['success' => true, 'data' => $report]);
    }

}
