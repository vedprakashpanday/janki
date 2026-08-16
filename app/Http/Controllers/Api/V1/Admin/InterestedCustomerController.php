<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterestedCustomer;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class InterestedCustomerController extends Controller
{



// ==========================================
    // 1. EXCEL TEMPLATE DOWNLOAD (Naya Function)
    // ==========================================
    public function downloadImportTemplate()
    {
        // Ye wahi strict columns hain jo user ko fill karne honge
        $headers = ['cust_name', 'mobile', 'email', 'address', 'remark', 'status', 'assigned_telecaller', 'reference', 'refer_by'];
        $csv = implode(',', $headers) . "\n";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="Import_Template.csv"');
    }

    // ==========================================
    // 2. AUTO-GENERATE PROVIDER ID (Naya Function)
    // ==========================================
    public function getNextProviderId()
    {
        // Database me se max provider_id nikalna (e.g., Pro_05 me se 5 nikalna)
        $latest = \App\Models\InterestedCustomer::where('provider_id', 'like', 'Pro_%')
            ->pluck('provider_id')
            ->map(function ($id) {
                return (int) str_replace('Pro_', '', $id);
            })
            ->max();

        $nextNumber = $latest ? $latest + 1 : 1;
        $nextId = 'Pro_' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        return response()->json(['status' => 'success', 'provider_id' => $nextId]);
    }

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


        // 🔥 NAYA CODE: Agar non-member lead already hai, toh use update karke member ka bana do
        if ($request->is_member == 1) {
            $existing = \App\Models\InterestedCustomer::where('mobile', $request->mobile)->first();
            if ($existing && $existing->is_member != 1) {
                $data['is_member'] = 1;
                $data['entry_date'] = $request->entry_date ?? date('Y-m-d');
                $existing->update($data);
                return response()->json(['status' => 'success', 'message' => 'Lead saved successfully.']);
            }
        }



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

        // 🔥 NAYA: History Nikalna
        $historyRecords = \App\Models\TelecallerAllocation::where('customer_id', $id)
            ->whereNotNull('called_at')
            ->whereNotNull('remark')
            ->orderBy('called_at', 'desc')
            ->get();
            
        $historyText = "";
        foreach ($historyRecords as $rec) {
            $date = \Carbon\Carbon::parse($rec->called_at)->format('d-M-y');
            $historyText .= "[{$date} | {$rec->call_status}]: {$rec->remark}\n";
        }
        $customer->remark_history = $historyText;

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

// =========================================================
    // 🔥 NEW SMART IMPORT (Handles JS Chunks & Head Office)
    // =========================================================
    public function import(\Illuminate\Http\Request $request)
    {
        // 1. Validate the incoming chunk request (Physical file is NOT expected here anymore)
        $request->validate([
            'leads' => 'required|array',
            'company_id' => 'required',
            'branch_id' => 'nullable', // 🔥 Isko nullable kiya taaki Head Office (blank) pass ho sake
            'provider_name' => 'required',
            'provider_id' => 'required',
        ]);

        $inserted = 0;
        $duplicates = 0;

        foreach ($request->leads as $row) {
            $mobile = isset($row['mobile']) ? trim($row['mobile']) : null;
            
            // Agar mobile number blank hai toh skip karo
            if (!$mobile) {
                continue;
            }

            // Database me duplicate check karo
            if (\App\Models\InterestedCustomer::where('mobile', $mobile)->exists()) {
                $duplicates++;
                continue;
            }

            // Nayi lead create karo
            \App\Models\InterestedCustomer::create([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id, // Head office ke case me ye null jayega
                'provider_id' => $request->provider_id,
                'provider_name' => $request->provider_name,
                'is_member' => $request->is_member ? 1 : 0,
                'member_id' => $request->is_member ? $request->member_id : null,
                
                // Excel columns data
                'cust_name' => $row['cust_name'] ?? 'Unknown',
                'mobile' => $mobile,
                'email' => $row['email'] ?? null,
                'address' => $row['address'] ?? null,
                'remark' => $row['remark'] ?? null,
                // 'status' aur 'required_for' ko update karein
                'status' => $request->is_member ? 'Pending' : ($row['status'] ?? 'Pending'), // Member ka hamesha Pending rahega
                'entry_status' => 'active',
                'required_for' => $row['required_phase'] ?? ($row['required_for'] ?? null), // required_phase column accept karega
                'assigned_telecaller' => $row['assigned_telecaller'] ?? null,
                'reference' => $row['reference'] ?? null,
                'refer_by' => $row['refer_by'] ?? null,
                'date' => date('Y-m-d'), // Aaj ki date
            ]);

            $inserted++;
        }

        return response()->json([
            'status' => 'success',
            'inserted' => $inserted,
            'db_duplicates' => $duplicates
        ]);
    }
public function checkMobile(Request $request)
    {
        $query = \App\Models\InterestedCustomer::where('mobile', $request->mobile);
        
        if ($request->has('exclude_id') && !empty($request->exclude_id)) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        // 🔥 NAYA: Sirf tab check karega jab already kisi member ne lead banayi ho
        $query->where('is_member', 1);
        
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

    public function allocateFreshCustomers(Request $request)
{
    $telecallerId = $request->input('telecaller_id');
    $targetCount = (int) $request->input('target_count', 10);
    $providerId = $request->input('provider_id'); // Optional: Agar specific provider select kiya ho

    $assignedCount = 0;
    $allocatedIds = [];

    // 1. Step 1: Agar Provider ID di gayi hai, toh pehle uske fresh leads strict serial (ASC) uthao
    if (!empty($providerId)) {
        $providerLeads = \App\Models\InterestedCustomer::where('provider_id', $providerId)
            ->where('entry_status', 'active')
            ->whereNull('assigned_telecaller') // ya jo bhi aapka unassigned column check ho
            ->orderBy('id', 'asc')
            ->limit($targetCount)
            ->pluck('id')
            ->toArray();

        $allocatedIds = array_merge($allocatedIds, $providerLeads);
    }

   // 2. Step 2: Agar target abhi bhi bacha hai (Fallback Logic), toh bachi hui general/normal leads uthao
    $remainingCount = $targetCount - count($allocatedIds);
    if ($remainingCount > 0) {
        $fallbackLeads = \App\Models\InterestedCustomer::whereNull('assigned_telecaller')
            ->where('entry_status', 'active')
            ->when(!empty($providerId), function($query) use ($providerId) {
                // Agar pehle provider select tha, toh uske alawa ya bachi hui baaki leads
                return $query->where(function($q) use ($providerId) {
                    $q->where('provider_id', '!=', $providerId)
                      ->orWhereNull('provider_id');
                });
            })
            ->orderBy('id', 'asc')
            ->limit($remainingCount)
            ->pluck('id')
            ->toArray();

        $allocatedIds = array_merge($allocatedIds, $fallbackLeads);
    }

    // 3. Step 3: Final Update / Assignment to Telecaller
    if (!empty($allocatedIds)) {
        \App\Models\InterestedCustomer::whereIn('id', $allocatedIds)->update([
            'assigned_telecaller' => $telecallerId,
            'updated_at' => now()
        ]);
        $assignedCount = count($allocatedIds);
    }

    return response()->json([
        'status' => 'success',
        'message' => "Successfully allocated {$assignedCount} leads!",
        'allocated_count' => $assignedCount
    ]);
}


// =======================================================
    // 🔥 GET MEMBER LEADS SUMMARY (For Admin Override) 🔥
    // =======================================================
    public function getMemberLeadsSummary($member_id)
    {
        $summary = \App\Models\InterestedCustomer::where('is_member', 1)
            ->where('member_id', $member_id)
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true, 
            'data' => $summary
        ]);
    }

public function getMemberPortalLeads(Request $request)
{
    // 🔥 STRICT SECURITY LOCK (Yeh already sahi hai)
    if (empty($request->member_id)) {
        return response()->json([
            'success' => true,
            'current_page' => 1,
            'last_page' => 1,
            'data' => []
        ]);
    }

    // Ye confirm karta hai ki sirf isi member ka aur is_member=1 wala data aaye
    $query = \App\Models\InterestedCustomer::query()
        ->where('member_id', $request->member_id)
        ->where('is_member', 1);

  // 🔥 DYNAMIC FILTERS (Mobile logic upgraded)
    if ($request->filled('mobile')) {
        $searchMobile = trim($request->mobile);
        $query->where(function($q) use ($searchMobile) {
            $q->where('mobile', 'like', '%' . $searchMobile . '%')
              ->orWhere('alternate_no', 'like', '%' . $searchMobile . '%');
        });
    }

    // 🔥 NAYA: Name ka filter
    if ($request->filled('name')) {
        $query->where('cust_name', 'like', '%' . trim($request->name) . '%');
    }
    
    if ($request->filled('address')) {
        $query->where('address', 'like', '%' . trim($request->address) . '%');
    }
    
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    if ($request->filled('date')) {
        $query->whereDate('created_at', $request->date); 
    }
        // ========================================================
        // 🔥 TELECALLER-STYLE CUMULATIVE PRIORITY SORTING 🔥
        // ========================================================
        $today = now()->toDateString();
        $threeDaysAgo = now()->subDays(3)->toDateString();

        // Ye array 'Rollover' status wala wahi hai jo humne Service file me define kiya tha
        $rolloverStatuses = "('Busy', 'Switch Off', 'Switched Off', 'DND/Call Rejected', 'DND/Call Restricted', 'Not Reachable', 'Not Reachable call', 'Not Answering', 'Not Answering Call', 'Incoming Call Not Available')";
        
        $priorityStatuses = "('Follow Up', 'Interested', 'Highly Interested', 'Connected', 'Connected ', 'Call Back Requested', 'On Hold')";

        $query->orderByRaw("
            CASE 
                -- RANK 1: Priority Status (Follow up, Interested, etc) jinka date aaj ya aaj se pehle hai (Ya date set hi na ho)
                WHEN status IN $priorityStatuses AND (followup_date IS NULL OR followup_date <= ?) THEN 1
                
                -- RANK 2: Koi bhi aur Follow-up date jo aaj ya aaj se purani ho (Missed followups)
                WHEN followup_date IS NOT NULL AND followup_date <= ? THEN 2
                
                -- RANK 3: Rollover Statuses jo pichle 3 din mein update hue ho
                WHEN status IN $rolloverStatuses AND DATE(updated_at) >= ? THEN 3
                
                -- RANK 4: Fresh/Pending Leads jo abhi tak touch nahi hui
                WHEN status IN ('Pending', 'pending', 'Pending status', 'General', 'general') THEN 4
                
                -- RANK 5: Baaki sab (Future follow-ups, Blacklist, Closed leads)
                ELSE 5
            END ASC
        ", [$today, $today, $threeDaysAgo])
        
        // Agar same rank me data hai, toh date ke hisaab se sort karo
        ->orderBy('followup_date', 'asc') 
        ->orderBy('updated_at', 'desc') 
        ->orderBy('id', 'desc'); 

        // Execute with pagination
        $leads = $query->paginate(20);

        return response()->json([
            'success' => true,
            'current_page' => $leads->currentPage(),
            'last_page' => $leads->lastPage(),
            'data' => $leads->items()
        ]);
    }
    // =========================================================
    // 🔥 GENERATE DYNAMIC CSV TEMPLATE FOR MEMBER 🔥
    // =========================================================
    public function downloadMemberTemplate()
    {
        $context = $this->getGlobalContext();
        
        // Member ki company aur branch ke hisaab se Phases nikalein
        $query = \App\Models\Phase::where('company_id', $context->company_id);
        if ($context->branch_id) {
            $query->where('branch_id', $context->branch_id);
        }
        $phaseNames = $query->pluck('phase_name')->toArray();
        
        // Excel me help text dalne ke liye phases ki list banayein
        $phaseHelpText = !empty($phaseNames) ? implode(' | ', $phaseNames) : 'Any Phase';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Member_Leads_Template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Member ke liye simplified columns
        $columns = ['cust_name', 'mobile', 'email', 'address', 'remark', 'required_phase', 'reference', 'refer_by'];

        $callback = function() use($columns, $phaseHelpText) {
            $file = fopen('php://output', 'w');
            
            // 1. Heading Row
            fputcsv($file, $columns);
            
            // 2. Sample Data Row (Jisse member ko idea lag jaye)
            fputcsv($file, [
                'Satyam Singh', 
                '9999999999', 
                'satyam@email.com', 
                'Patna, Bihar', 
                'Call after 5PM', 
                $phaseHelpText, // Yahan usko apni company ke saare Phases dikhenge
                'Facebook Ads', 
                'Self'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
