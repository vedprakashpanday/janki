<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Letterhead;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LetterheadController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Letterhead::with('branch', 'company')->orderBy('id', 'desc');

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC (Daily vs Directory)
        // ==========================================
        if ($request->query('filter') === 'daily') {
            $query->whereDate('created_at', today());
        }

        // ==========================================
        // 🛡️ 2. SMART SCOPING (Master HO vs Sub HO vs Branch)
        // ==========================================
        $isMasterHO = false;
        if ($context->is_employee && empty($context->branch_id) && !empty($context->company_id)) {
            $comp = Company::find($context->company_id);
            if ($comp && empty($comp->parent_id)) {
                $isMasterHO = true;
            }
        }

        if (!$context->is_god && !$context->is_director && !$isMasterHO) {
            if ($context->company_id) {
                $query->where('company_id', $context->company_id);
            }
            if ($context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $request->validate([
            'ref_no' => 'required',
            'ref_year' => 'required',
            'letter_date' => 'required',
            'message' => 'required'
        ]);

        // 🔥 PERMISSION CHECK: Direct ya Request
        $hasDirect = $context->is_god || $context->is_director;
        if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
            $userPerms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            if (in_array('letterhead_add_direct', $userPerms) || in_array('letterhead_dir_add_direct', $userPerms)) {
                $hasDirect = true;
            }
        }

        $companyId = ($request->company_id === 'global' || empty($request->company_id)) ? null : $request->company_id;
        $branchId = ($request->branch_id === 'all' || empty($request->branch_id)) ? null : $request->branch_id;

        $company = $companyId ? Company::find($companyId) : Company::whereNull('parent_id')->first();
        if (!$company) $company = Company::find(1);

        $compCode = $company ? strtoupper($company->company_code) : 'COMP';
        $stateCode = 'ST'; $distCode = 'DIST'; $branchSeq = 'HO';

        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch && $branch->branch_id) {
                $bParts = explode('/', $branch->branch_id);
                $compCode = $bParts[0] ?? $compCode;
                $stateCode = $bParts[1] ?? 'ST';
                $distCode = $bParts[2] ?? 'DIST';
                $branchSeq = $bParts[3] ?? '01';
            }
        } else {
            $branchSeq = 'HO';
            if ($company) {
                $stateLower = strtolower(trim($company->state));
                $stateMap = ['bihar' => 'BIH', 'uttar pradesh' => 'UP', 'delhi' => 'DL', 'jharkhand' => 'JHA', 'west bengal' => 'WB'];
                $stateCode = $stateMap[$stateLower] ?? strtoupper(substr($stateLower, 0, 3));
                if (empty($stateCode)) $stateCode = 'ST';

                $distLower = strtolower(trim($company->district));
                $distMap = ['madhubani' => 'MAD', 'darbhanga' => 'DBJ', 'gopalganj' => 'GOPJ', 'saharsa' => 'SAH', 'patna' => 'PAT'];
                $distCode = $distMap[$distLower] ?? strtoupper(substr($distLower, 0, 3));
                if (empty($distCode)) $distCode = 'DIST';
            }
        }

        $fullRefNo = "{$compCode}/{$stateCode}/{$distCode}/{$branchSeq}/{$request->ref_no}/{$request->ref_year}";

        if (Letterhead::where('ref_no', $fullRefNo)->exists()) {
            return response()->json(['status' => 'error', 'message' => "Reference Number '$fullRefNo' is already generated."], 400);
        }

        $data = $request->except(['_token', 'company_id', 'branch_id']);
        $data['ref_no'] = $fullRefNo; 
        $data['company_id'] = $companyId;
        $data['branch_id'] = $branchId;
        $data['status'] = $hasDirect ? 'active' : 'pending';

        if (strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        $letterhead = Letterhead::create($data);
        
        $msg = $hasDirect ? "Letterhead Saved Successfully!" : "Letterhead Request Sent for Approval!";
        return response()->json(['status' => 'success', 'message' => $msg]);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $letterhead = Letterhead::with('branch')->findOrFail($id);

        if (!$context->is_god && !$context->is_director) {
            if ($context->branch_id && $letterhead->branch_id != $context->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $letterhead]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $data = $request->except(['_token', 'ref_no', '_method']);

        if (strtolower($data['emp_code'] ?? '') === 'all') {
            $data['emp_code'] = 'All';
        }

        $letterhead = Letterhead::findOrFail($id);

        $hasDirect = $context->is_god || $context->is_director;
        if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
            $userPerms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            if (in_array('letterhead_add_direct', $userPerms) || in_array('letterhead_dir_add_direct', $userPerms)) {
                $hasDirect = true;
            }
        }

        if ($request->company_id === 'global') $request->merge(['company_id' => null]);
        if ($request->branch_id === 'all' || empty($request->branch_id)) $request->merge(['branch_id' => null]); 

        if (!$hasDirect) {
            $data['status'] = 'pending';
        }

        $letterhead->update($data);
        return response()->json(['status' => 'success', 'message' => 'Letterhead Updated Successfully']);
    }

    // ========================================================
    // 🔥 APPROVE / REJECT / BULK DELETE
    // ========================================================
    public function approve($id)
    {
        $letterhead = Letterhead::findOrFail($id);
        $letterhead->update(['status' => 'active']);
        return response()->json(['status' => 'success', 'message' => 'Letterhead Approved!']);
    }

    public function reject($id)
    {
        $letterhead = Letterhead::findOrFail($id);
        $letterhead->update(['status' => 'inactive']);
        return response()->json(['status' => 'success', 'message' => 'Letterhead Rejected!']);
    }

    public function destroy($id)
    {
        Letterhead::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No letterheads selected!'], 400);
        }

        Letterhead::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected letterheads deleted successfully!']);
    }

    // ========================================================
    // 🔥 7 TABLES SEARCH (WITH DIRECTORS & CEOS)
    // ========================================================
    public function searchEntities(Request $request)
    {
        $search = $request->get('q');
        if (strlen($search) < 3) return response()->json([]);

        $emps = DB::table('adm_regist')->where('full_name', 'like', "%{$search}%")->orWhere('member_id', 'like', "%{$search}%")
            ->select('member_id as id', 'full_name as name', DB::raw("'Employee' as type"))->get();
        $mems = DB::table('members')->where('member_name', 'like', "%{$search}%")->orWhere('member_id', 'like', "%{$search}%")
            ->select('member_id as id', DB::raw("COALESCE(member_name) as name"), DB::raw("'Member' as type"))->get();
        $custs = DB::table('customers')->where('customer_name', 'like', "%{$search}%")->orWhere('customer_id', 'like', "%{$search}%")
            ->select('customer_id as id', DB::raw("COALESCE(customer_name) as name"), DB::raw("'Customer' as type"))->get();
        $vens = DB::table('vendors')->where('full_name', 'like', "%{$search}%")->orWhere('vendor_id', 'like', "%{$search}%")
            ->select('vendor_id as id', DB::raw("COALESCE(full_name) as name"), DB::raw("'Vendor' as type"))->get();
        $lands = DB::table('landowners')->where('land_owner_name', 'like', "%{$search}%")->orWhere('land_owner_id', 'like', "%{$search}%")->orWhere('land_owner_id', 'like', "%{$search}%")
            ->select(DB::raw("COALESCE(land_owner_id) as id"), DB::raw("COALESCE(land_owner_name) as name"), DB::raw("'Land Owner' as type"))->get();
        $dirs = DB::table('directors')->where('full_name', 'like', "%{$search}%")->orWhere('director_id', 'like', "%{$search}%")
            ->select('director_id as id', 'full_name as name', DB::raw("'Director' as type"))->get();
        $ceos = DB::table('super_admins')->where('full_name', 'like', "%{$search}%")->orWhere('ceo_id', 'like', "%{$search}%")
            ->select('ceo_id as id', 'full_name as name', DB::raw("'CEO' as type"))->get();

        $all = $emps->concat($mems)->concat($custs)->concat($vens)->concat($lands)->concat($dirs)->concat($ceos);
        $formatted = $all->map(function($item) {
            return ['id' => $item->id, 'text' => $item->name . ' (' . $item->id . ') - [' . $item->type . ']'];
        });

        return response()->json($formatted);
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/letterheads/images'), $filename);
            return response()->json(['location' => asset('uploads/letterheads/images/' . $filename)]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function getNextRefNo()
    {
        $lastRecord = Letterhead::orderBy('id', 'desc')->first();
        $nextRef = 53; 

        if ($lastRecord && $lastRecord->ref_no) {
            $parts = explode('/', $lastRecord->ref_no);
            if (count($parts) >= 5) {
                $seriesPart = $parts[count($parts) - 2]; 
                if (is_numeric($seriesPart)) $nextRef = intval($seriesPart) + 1;
            } elseif (is_numeric($lastRecord->ref_no)) {
                $nextRef = intval($lastRecord->ref_no) + 1;
            }
        }
        return response()->json(['status' => 'success', 'next_ref_no' => $nextRef]);
    }

  // ========================================================
    // 🔥 SEND TO NOTICE BOARD & SMART NOTIFICATION ROUTING
    // ========================================================
    public function sendToNoticeBoard(Request $request)
    {
        try {
            $ids = $request->ids;
            // Frontend se aane wala reply choice (default 0 agar na aaye toh)
            $requiresReply = $request->input('requires_reply', 0); 

            if (empty($ids)) {
                return response()->json(['status' => 'error', 'message' => 'No letterheads selected!'], 400);
            }

            $letterheads = \App\Models\Letterhead::whereIn('id', $ids)->get();
            $sentCount = 0;

            foreach ($letterheads as $letterhead) {
                $empCode = strtolower(trim($letterhead->emp_code));
                
                // Skip if Manual (no specific target)
                if ($empCode === 'manual') continue;

                $targetAudience = 'individual';
                $entityType = null;
                $entityId = null;
                $notifyUsers = collect();

                // 1. Audience & User Mapping Logic 
                if ($empCode === 'all employees' || $empCode === 'all') {
                    $targetAudience = 'employee';
                    $notifyUsers = \App\Models\Employee::where('emp_status', 'active')->get();
                } elseif ($empCode === 'all members') {
                    $targetAudience = 'member';
                    $notifyUsers = \App\Models\Member::where('status', 'active')->get();
                } elseif ($empCode === 'all directors') {
                    $targetAudience = 'director'; 
                    $notifyUsers = \App\Models\Director::where('status', 'active')->get();
                } elseif ($empCode === 'all ceos') {
                    $targetAudience = 'super_admin';
                    $notifyUsers = \App\Models\SuperAdmin::where('status', 'active')->get();
                } else {
                    // Specific Individual
                    $targetAudience = 'individual';
                    $entityId = $letterhead->emp_code;
                    
                    // Cascade search to find Exact User Model & assign entity_type
                    $user = \App\Models\Employee::where('member_id', $letterhead->emp_code)->first();
                    if ($user) {
                        $entityType = 'employee';
                        $notifyUsers->push($user);
                    } else {
                        $user = \App\Models\Member::where('member_id', $letterhead->emp_code)->first();
                        if ($user) {
                            $entityType = 'member';
                            $notifyUsers->push($user);
                        } else {
                            $user = \App\Models\Customer::where('customer_id', $letterhead->emp_code)->first();
                            if ($user) {
                                $entityType = 'customer';
                                $notifyUsers->push($user);
                            } else {
                                $user = \App\Models\Director::where('director_id', $letterhead->emp_code)->first();
                                if ($user) {
                                    $entityType = 'director';
                                    $notifyUsers->push($user);
                                } else {
                                    $user = \App\Models\SuperAdmin::where('ceo_id', $letterhead->emp_code)->first();
                                    if ($user) {
                                        $entityType = 'super_admin';
                                        $notifyUsers->push($user);
                                    }
                                }
                            }
                        }
                    }
                }

                // 2. Create Notice Record
                $notice = \App\Models\Notice::create([
                    'title' => $letterhead->subject ?: 'Official Letterhead - ' . $letterhead->ref_no,
                    'notice_date' => $letterhead->letter_date,
                    'content' => $letterhead->message,
                    'target_audience' => $targetAudience,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'company_id' => $letterhead->company_id,
                    'target_company_id' => $letterhead->company_id,
                    'target_branch_id' => $letterhead->branch_id,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                    // 🔥 DIALOG WALI VALUE YAHAN BIND HOGI
                    'requires_reply' => $requiresReply 
                ]);

                // 3. Smart Notification Dispatcher (Direct DB insert)
                $now = now();
                $notificationsData = [];

                foreach ($notifyUsers as $nu) {
                    $modelType = get_class($nu);
                    
                    // SMART URL ROUTING BASED ON MODEL TYPE
                    $userPortalUrl = '/admin/my-notices'; // Default Fallback
                    if ($modelType === 'App\Models\Employee') {
                        $userPortalUrl = '/employee/my-notices';
                    } elseif ($modelType === 'App\Models\Member') {
                        $userPortalUrl = '/member/my-notices';
                    } elseif ($modelType === 'App\Models\Customer') {
                        $userPortalUrl = '/customer/my-notices';
                    } elseif ($modelType === 'App\Models\Director' || $modelType === 'App\Models\SuperAdmin') {
                        $userPortalUrl = '/admin/my-notices';
                    }

                    $notificationsData[] = [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'type' => 'App\Notifications\NoticePublished',
                        'notifiable_type' => $modelType,
                        'notifiable_id' => $nu->id,
                        'data' => json_encode([
                            'title' => 'New Official Notice',
                            'message' => 'Ref: ' . $letterhead->ref_no . ' | Please check your notice board.',
                            'url' => $userPortalUrl,
                            'icon' => 'fa-envelope-open-text',
                            'colorClass' => 'text-primary'
                        ]),
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($notificationsData)) {
                    $chunks = array_chunk($notificationsData, 500);
                    foreach ($chunks as $chunk) {
                        \Illuminate\Support\Facades\DB::table('notifications')->insert($chunk);
                    }
                }

                $sentCount++;
            }

            return response()->json([
                'status' => 'success', 
                'message' => "Successfully sent {$sentCount} letterhead(s) to the Notice Board!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backend Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ], 500);
        }
    }
    public function printPreview($id)
    {
        $letterhead = \App\Models\Letterhead::with('branch', 'company')->findOrFail($id);
        
        $authUser = auth('sanctum')->user() ?? auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if ($authUser && !$authUser->hasRole(['CEO', 'Director']) && !in_array($authUser->email, $developerEmails)) {
            if ($letterhead->branch_id != $authUser->branch_id) {
                abort(403, 'Strict Security: You are not authorized to view or print letterheads of other branches.');
            }
        }
        
        $empCode = $letterhead->emp_code;
        $paid_to_name = null;
        $paid_to_id = null;
        $paid_to_mobile = null;
        $paid_to_address = null;
        $paid_to_relation = null;
        $paid_to_doj = '-';
        $paid_to_designation = null;

        if ($empCode && strtolower($empCode) !== 'all') {
            $emp = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $empCode)->first();
            if ($emp) {
                $paid_to_name = $emp->full_name ?? null;
                $paid_to_id = $emp->member_id;
                $paid_to_mobile = $emp->contact_no ?? $emp->mobile ?? null;
                $paid_to_address = $emp->communication_address ?? $emp->address ?? null;
                $paid_to_relation = $emp->father_spouse_name ?? null;
                $paid_to_doj = $emp->doj ?? '-';
                
                $paid_to_designation = 'Employee'; 
                if (!empty($emp->designation_id)) {
                    $desg = \Illuminate\Support\Facades\DB::table('designations')->where('id', $emp->designation_id)->first();
                    if ($desg) $paid_to_designation = $desg->designation_name;
                }
            }
            if (!$paid_to_name) {
                $mem = \Illuminate\Support\Facades\DB::table('members')->where('member_id', $empCode)->first();
                if ($mem) {
                    $paid_to_name = $mem->member_name ?? $mem->full_name ?? null;
                    $paid_to_id = $mem->member_id;
                    $paid_to_mobile = $mem->mobile ?? null;
                    $paid_to_address = $mem->address ?? null;
                    $paid_to_relation = $mem->so_do_name ?? $mem->father_spouse_name ?? null;
                    $paid_to_doj = $mem->doj ?? '-';
                    $paid_to_designation = $mem->designation ?? 'Member';
                }
            }
            if (!$paid_to_name) {
                $cust = \Illuminate\Support\Facades\DB::table('customers')->where('customer_id', $empCode)->first();
                if ($cust) {
                    $paid_to_name = $cust->customer_name ?? $cust->full_name ?? null;
                    $paid_to_id = $cust->customer_id;
                    $paid_to_mobile = $cust->mobile ?? $cust->customer_mobile ?? null;
                    $paid_to_address = $cust->address ?? null;
                    $paid_to_relation = $cust->so_do_wo ?? null;
                    $paid_to_doj = $cust->booking_date ?? '-';
                    $paid_to_designation = 'Customer';
                }
            }
            if (!$paid_to_name) {
                $ven = \Illuminate\Support\Facades\DB::table('vendors')->where('vendor_id', $empCode)->first();
                if ($ven) {
                    $paid_to_name = $ven->full_name ?? $ven->vendor_name ?? null;
                    $paid_to_id = $ven->vendor_id;
                    $paid_to_mobile = $ven->contact_no ?? $ven->mobile ?? null;
                    $paid_to_address = $ven->communication_address ?? $ven->address ?? null;
                    $paid_to_relation = $ven->father_spouse_name ?? null;
                    $paid_to_designation = $ven->vendor_type ?? 'Vendor';
                }
            }
            if (!$paid_to_name) {
                $land = \Illuminate\Support\Facades\DB::table('landowners')
                    ->where('land_owner_id', $empCode)
                    ->orWhere('land_owner_id', $empCode)->first();
                if ($land) {
                    $paid_to_name = $land->landowner_name ?? $land->full_name ?? null;
                    $paid_to_id = $land->landowner_id ?? $land->land_owner_id ?? null;
                    $paid_to_mobile = $land->mobile1 ?? $land->mobile ?? null;
                    $paid_to_address = $land->address ?? null;
                    $paid_to_relation = $land->relation_name ?? null;
                    $paid_to_designation = 'Land Owner';
                }
            }
        }

        $records = [
            'ref_no' => $letterhead->ref_no,
            'letter_date' => $letterhead->letter_date,
            'letter_title' => $letterhead->subject ?? 'LETTERHEAD',
            'message' => $letterhead->message,
            'emp_code' => $letterhead->emp_code,

            'paid_to_name' => $paid_to_name ?: $letterhead->paid_to,
            'paid_to_id' => $paid_to_id,
            'paid_to_mobile' => $paid_to_mobile,
            'paid_to_address' => $paid_to_address ?: $letterhead->paid_to_address,
            'paid_to_relation' => $paid_to_relation,
            'paid_to_doj' => $paid_to_doj,
            'paid_to_designation' => $paid_to_designation
        ];

        $companyForHeader = (empty($letterhead->company_id) || $letterhead->company_id === 'global') ? (\App\Models\Company::whereNull('parent_id')->first() ?? \App\Models\Company::find(1)) : \App\Models\Company::find($letterhead->company_id);
        $branchForHeader = (empty($letterhead->branch_id) || $letterhead->branch_id === 'all') ? null : \App\Models\Branch::find($letterhead->branch_id);

        return view('admin.print_letterhead', compact('records', 'companyForHeader', 'branchForHeader'));
    }
}