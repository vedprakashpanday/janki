<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelecallerAllocation;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TelecallingController extends Controller
{
   // ======================================================================
    // 1. GET ALLOCATIONS (ULTRA SPEED OPTIMIZED & STRICT FILTERS)
    // ======================================================================
    public function getAllocations(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // Base query
        $query = \App\Models\TelecallerAllocation::query();

        // ==========================================
        // 1. RBAC SECURITY SCOPE
        // ==========================================
        if ($context->is_god) {
            // God scope
        } elseif ($context->is_director) {
            $companyId = $context->company_id;
            $request->merge(['company_ids' => $companyId]);
            $query->whereHas('task', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        } else {
            $query->where('assignee_type', get_class($user))
                  ->where('assignee_id', $user->id);
        }

        // ==========================================
        // 1.5 CASCADING HIERARCHY FILTERS
        // ==========================================
        $compIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
        $branchIds = $request->filled('branch_ids') ? explode(',', $request->branch_ids) : [];
        $deptIds = $request->filled('department_ids') ? explode(',', $request->department_ids) : [];
        $desigIds = $request->filled('designation_ids') ? explode(',', $request->designation_ids) : [];

        if (!empty($compIds) || !empty($branchIds) || !empty($deptIds) || !empty($desigIds)) {
            $query->whereHasMorph('assignee', ['App\Models\Employee', 'App\Models\Member'], function($q, $type) use ($compIds, $branchIds, $deptIds, $desigIds) {
                if (!empty($compIds)) $q->whereIn('company_id', $compIds);
                if (!empty($branchIds)) {
                    $normalBIds = []; $hoCIds = [];
                    foreach ($branchIds as $bId) {
                        if (str_starts_with($bId, 'HO_')) $hoCIds[] = str_replace('HO_', '', $bId);
                        else $normalBIds[] = $bId;
                    }
                    $q->where(function($sq) use ($normalBIds, $hoCIds) {
                        if (count($normalBIds) > 0) $sq->whereIn('branch_id', $normalBIds);
                        if (count($hoCIds) > 0) {
                            $sq->orWhere(function($ssq) use ($hoCIds) {
                                $ssq->whereIn('company_id', $hoCIds)->whereNull('branch_id');
                            });
                        }
                    });
                }
                if (!empty($deptIds)) $q->whereIn('department_id', $deptIds);
                if (!empty($desigIds)) $q->whereIn('designation_id', $desigIds);
            });
        }

        if ($request->filled('assignee_ids')) {
            $query->whereIn('assignee_id', explode(',', $request->assignee_ids));
        }

        // ==========================================
        // 2. CALL STATUS & SMART DATE MATCHING
        // ==========================================
        $isFilterApplied = $request->boolean('is_filter');
        $fStatus = $request->filled('call_status') ? trim($request->call_status) : 'all';
        $leadType = $request->filled('lead_type') ? $request->lead_type : 'all';

        // 🔥 A. INDEPENDENT STATUS FILTER (Sabse pehle status match karo)
        if ($fStatus !== 'all') {
            if (in_array(strtolower($fStatus), ['pending', 'pending status'])) {
                $query->whereIn('telecaller_allocations.call_status', ['Pending', 'pending', 'Pending status', ''])
                      ->whereNull('telecaller_allocations.called_at');
                      
                if ($leadType === 'fresh') $query->where('telecaller_allocations.is_rollover', 0);
                elseif ($leadType === 'rollover') $query->where('telecaller_allocations.is_rollover', 1);
            } else {
                $query->where(function($q) use ($fStatus) {
                    if ($fStatus === 'Connected') $q->whereIn('telecaller_allocations.call_status', ['Connected', 'Connected ']);
                    else $q->where('telecaller_allocations.call_status', $fStatus);
                })->whereNotNull('telecaller_allocations.called_at');
            }
        }

        // 🔥 B. INDEPENDENT NEW DATE FILTERS (Strict Y-m-d format & Native whereDate)
        if ($request->filled('followup_date_filter')) {
            $fDate = date('Y-m-d', strtotime($request->followup_date_filter));
            $query->whereDate('telecaller_allocations.followup_date', $fDate);
        }
        if ($request->filled('called_at_filter')) {
            $cDate = date('Y-m-d', strtotime($request->called_at_filter));
            $query->whereDate('telecaller_allocations.called_at', $cDate);
        }

        // 🔥 C. CREATED/MIXED DATE FILTER (Fixed to use whereDate, whereMonth, whereYear)
        $filterDate = $request->filled('date') ? date('Y-m-d', strtotime($request->date)) : null;
        $filterMonth = $request->filled('month') ? $request->month : null;

        // Fallback: Agar koiiii bhi filter na laga ho, tabhi aaj ki date pakadna
        if (!$isFilterApplied && empty($filterDate) && empty($filterMonth) && !$request->filled('followup_date_filter') && !$request->filled('called_at_filter')) {
            $filterDate = now()->toDateString();
        }

        if (!empty($filterDate) || !empty($filterMonth)) {
            $query->where(function ($mainQ) use ($fStatus, $filterDate, $filterMonth) {
                
                $year = $filterMonth ? date('Y', strtotime($filterMonth)) : null;
                $month = $filterMonth ? date('m', strtotime($filterMonth)) : null;

                if (in_array(strtolower($fStatus), ['pending', 'pending status'])) {
                    // Pending => Check Created At
                    if ($filterDate) {
                        $mainQ->whereDate('telecaller_allocations.created_at', $filterDate);
                    } elseif ($filterMonth) {
                        $mainQ->whereYear('telecaller_allocations.created_at', $year)
                              ->whereMonth('telecaller_allocations.created_at', $month);
                    }
                } 
                elseif ($fStatus !== 'all') {
                    // Called => Check Called At
                    if ($filterDate) {
                        $mainQ->whereDate('telecaller_allocations.called_at', $filterDate);
                    } elseif ($filterMonth) {
                        $mainQ->whereYear('telecaller_allocations.called_at', $year)
                              ->whereMonth('telecaller_allocations.called_at', $month);
                    }
                } 
                else {
                    // All Status => Mixed Logic
                    if ($filterDate) {
                        $mainQ->where(function($dateQ) use ($filterDate) {
                            $dateQ->whereDate('telecaller_allocations.called_at', $filterDate)
                                  ->orWhere(function($pendingQ) use ($filterDate) {
                                      $pendingQ->whereNull('telecaller_allocations.called_at')
                                               ->whereDate('telecaller_allocations.created_at', $filterDate);
                                  })
                                  ->orWhereDate('telecaller_allocations.followup_date', $filterDate);
                        });
                    } elseif ($filterMonth) {
                        $mainQ->where(function($dateQ) use ($year, $month) {
                            $dateQ->whereYear('telecaller_allocations.called_at', $year)
                                  ->whereMonth('telecaller_allocations.called_at', $month)
                                  ->orWhere(function($pendingQ) use ($year, $month) {
                                      $pendingQ->whereNull('telecaller_allocations.called_at')
                                               ->whereYear('telecaller_allocations.created_at', $year)
                                               ->whereMonth('telecaller_allocations.created_at', $month);
                                  });
                        });
                    }
                }
            });
        }


        // ==========================================
        // 3.5 PREFERRED LOCATION FILTER
        // ==========================================
        if ($request->filled('preferred_location')) {
            $prefLoc = $request->preferred_location;
            $query->where(function($q) use ($prefLoc) {
                $q->where('telecaller_allocations.preferred_location', 'LIKE', "%{$prefLoc}%")
                  ->orWhereHas('customer', function($cq) use ($prefLoc) {
                      $cq->where('preferred_location', 'LIKE', "%{$prefLoc}%");
                  });
            });
        }

        // ==========================================
        // 3. SEARCH FILTER
        // ==========================================
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('telecaller_allocations.id', 'desc');
        $isExport = $request->boolean('is_export'); 
        
        $countQuery = clone $query;
        $totalCount = $countQuery->count(); 
        
        $employeeSummary = [];

        // Summary Data Generate
        if ((int)($request->offset ?? 0) == 0 && !$isExport) {
            if (!$context->is_god && !$context->is_director) {
                $employeeSummary[] = [
                    'id' => $user->id,
                    'type' => get_class($user),
                    'name' => $user->name ?? $user->full_name ?? 'You'
                ];
            } else {
                $summaryQuery = clone $query;
                $summaryQuery->getQuery()->orders = null; 
                
                $employeeSplit = $summaryQuery->select('assignee_id', 'assignee_type')->distinct()->get();
                $groupedAssignees = $employeeSplit->groupBy('assignee_type');

                foreach ($groupedAssignees as $type => $records) {
                    if (class_exists($type)) {
                        $ids = $records->pluck('assignee_id')->toArray();
                        $users = $type::whereIn('id', $ids)->get()->keyBy('id'); 
                        
                        foreach ($records as $split) {
                            if (isset($users[$split->assignee_id])) {
                                $empRecord = $users[$split->assignee_id];
                                $employeeSummary[] = [
                                    'id' => $empRecord->id,
                                    'type' => $split->assignee_type,
                                    'name' => $empRecord->full_name ??  'Unknown'
                                ];
                            }
                        }
                    }
                }
            }
        }

        // ==========================================================
        // 🔥 STRICT COLUMN SELECTION (SELECT * HATA DIYA) 🔥
        // ==========================================================
        $query->select(
            'telecaller_allocations.id', 'telecaller_allocations.customer_id', 'telecaller_allocations.task_id', 
            'telecaller_allocations.phase_id', 'telecaller_allocations.assignee_id', 'telecaller_allocations.assignee_type', 
            'telecaller_allocations.call_status', 'telecaller_allocations.assigned_status', 'telecaller_allocations.remark', 
            'telecaller_allocations.is_rollover', 'telecaller_allocations.created_at', 'telecaller_allocations.called_at', 
            'telecaller_allocations.followup_date', 'telecaller_allocations.followup_month', 'telecaller_allocations.followup_time'
        );

        $query->with([
            // Customer
            'customer:id,cust_name,mobile,alternate_no,status,budget,interested_for,refer_by,email,address,dob,anniversary_date', 
            
            // Task aur Phase
            'task:id,title,target_count,achieved_count', 
            'phase:id,phase_name,phase_details,phase_image', 
            
            // 🔥 adm_regist (Assignee) - Strict columns
            'assignee:id,full_name,member_id,company_id,branch_id,department_id,designation_id,emp_status', 
            
            // 🔥 Hierarchy Relations
            'assignee.company:id,company_name,status', 
            'assignee.branch:id,company_id,branch_name,status', 
            'assignee.department:id,company_ids,branch_ids,status,department_name', 
            'assignee.designation:id,department_id,designation_name'
        ]);

        if ($isExport) {
            $data = $query->get();
            $hasMore = false;
        } else {
            $limit = 20;
            $offset = (int) ($request->offset ?? 0);
            $data = $query->skip($offset)->take($limit + 1)->get();
            
            $hasMore = $data->count() > $limit;
            if ($hasMore) {
                $data->pop(); 
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'has_more' => $hasMore,
            'total_count' => $totalCount, 
            'employee_summary' => $employeeSummary 
        ]);
    }

   // ======================================================================
    // 2. UPDATE FEEDBACK & PROGRESS BAR (With Smart Late Call Algorithm)
    // ======================================================================
    public function updateFeedback(Request $request, $id)
    {
        // 🔥 NAYA: time aur duration ko mandatory validate karna
        $request->validate([
            'call_status' => 'required|string',
            'calling_time' => 'required',
            'calling_duration' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
           $allocation = TelecallerAllocation::select(
                'id', 'customer_id', 'task_id', 'assignee_id', 'assignee_type', 'call_status', 'remark'
            )->with(['customer' => function($q) {
                $q->select('id', 'alternate_no', 'email', 'address', 'dob', 'anniversary_date', 'status', 'budget', 'interested_for', 'followup_date', 'followup_month', 'remark', 'called_by', 'assigned_telecaller', 'preferred_location', 'mobile');
            }])->findOrFail($id);

            $user = auth()->user();
            $now = now();
            $todayStr = $now->toDateString();

            // =========================================================
            // 🧠 THE "LATE CALLING" ALGORITHM (Step 4 Logic)
            // =========================================================
            $isLate = false;
            $lateByMinutes = 0;
            
            // User ka time aaj ki date ke saath merge kiya
            $callingTime = \Carbon\Carbon::parse($todayStr . ' ' . $request->calling_time);
            
            // 1. Current Call Buffer Check (Calling Time + Duration + 1 Min)
            $expectedEndTime = $callingTime->copy()->addMinutes((int)$request->calling_duration)->addMinute();
            
            // Agar system time expected time se 20 seconds se bhi zyada aage hai
            if ($now->greaterThan($expectedEndTime->copy()->addSeconds(20))) {
                $isLate = true;
                $lateByMinutes += $now->diffInMinutes($expectedEndTime);
            }

            // 2. Gap From Previous Call Check
            $lastCall = TelecallerAllocation::where('assignee_id', $allocation->assignee_id)
                ->where('assignee_type', $allocation->assignee_type)
                ->whereDate('called_at', $todayStr)
                ->where('id', '!=', $allocation->id)
                ->whereNotNull('called_at')
                ->latest('called_at')
                ->first();

            if ($lastCall) {
                $lastCallTime = \Carbon\Carbon::parse($lastCall->called_at);
                // Last call ke baad 1 min ka gap allowed hai
                $expectedNextCallTime = $lastCallTime->copy()->addMinute();
                
                if ($callingTime->greaterThan($expectedNextCallTime)) {
                    $isLate = true;
                    $gapLate = $callingTime->diffInMinutes($expectedNextCallTime);
                    $lateByMinutes += $gapLate;
                }
            }
            // =========================================================

           // 🔥 Allocation table update (Naye columns ke sath)
           $allocation->update([
                'call_status'      => $request->call_status,
                'interested_for'   => $request->interested_for,
                'budget'           => $request->budget,
                'followup_date'    => $request->followup_date,
                'followup_time'    => $request->followup_time,
                'followup_month'   => $request->followup_month,
                'dob'              => $request->dob,
                'anniversary_date' => $request->anniversary_date,
                'preferred_location' => $request->preferred_location,
                'remark'           => $request->remark,
                'state'            => $request->state,              // 🆕
                'calling_time'     => $request->calling_time,       // 🆕
                'calling_duration' => $request->calling_duration,   // 🆕
                'is_late'          => $isLate ? 1 : 0,              // 🆕
                'late_by_minutes'  => $lateByMinutes,               // 🆕
                'called_at'        => $now, 
            ]);

            // 🔥 Customer Master table update (State ke sath)
            if ($allocation->customer) {
                $allocation->customer->update([
                    'alternate_no'        => $request->alternate_no, 
                    'email'               => $request->email,        
                    'address'             => $request->address,      
                    'dob'                 => $request->dob,          
                    'anniversary_date'    => $request->anniversary_date, 
                    'preferred_location'  => $request->preferred_location,
                    'status'              => $request->call_status,
                    'budget'              => $request->budget,
                    'interested_for'      => $request->interested_for,
                    'followup_date'       => $request->followup_date,
                    'followup_month'      => $request->followup_month,
                    'remark'              => $request->remark,
                    'state'               => $request->state,       // 🆕
                    'called_by'           => $user->name ?? $user->full_name ?? 'Telecaller',
                    'assigned_telecaller' => $user->member_id ?? $user->id,
                ]);
            }

            // 🔥 BACKLOG & TODAY PROGRESS TRACKING (260/250 SUPPORT)
            $achievedCount = TelecallerAllocation::where('assignee_id', $allocation->assignee_id)
                ->where('assignee_type', $allocation->assignee_type)
                ->whereDate('called_at', $todayStr) 
                ->whereNotIn('call_status', ['Pending', 'pending', 'Pending status'])
                ->count();

            $todayTask = Task::where('assignee_id', $allocation->assignee_id)
                ->where('assignee_type', $allocation->assignee_type)
                ->whereDate('created_at', $todayStr)
                ->first();

            if ($todayTask) {
                $todayTask->achieved_count = $achievedCount; 
                
                if ($todayTask->achieved_count >= $todayTask->target_count && $todayTask->status !== 'Completed') {
                    $todayTask->status = 'Completed';
                    \App\Models\TaskProgressLog::create([
                        'task_id' => $todayTask->id,
                        'actor_type' => get_class($user),
                        'actor_id' => $user->id,
                        'log_type' => 'progress_update',
                        'message_or_remark' => "🎉 Target Met or Overachieved! Total calls completed today: {$achievedCount}.",
                        'entries_completed' => 0
                    ]);
                } elseif ($todayTask->status === 'Pending' && $achievedCount > 0) {
                    $todayTask->status = 'In-Progress';
                }
                
                $todayTask->save();

                // 🔥 THE MAGIC: Agar Telecaller Late hua hai, toh uske personal task me log ban jayega!
                if ($isLate) {
                    $phone = $allocation->customer ? $allocation->customer->mobile : 'N/A';
                    $timeFormatted = \Carbon\Carbon::parse($request->calling_time)->format('h:i A');
                    
                    \App\Models\TaskProgressLog::create([
                        'task_id' => $todayTask->id,
                        'actor_type' => get_class($user),
                        'actor_id' => $user->id,
                        'log_type' => 'late_call_alert', // Naya tag late calls ke liye
                        'message_or_remark' => "🚨 Late Alert: Customer {$phone} ki details update karne me {$lateByMinutes} mins ki deri ki gayi. (Call: {$timeFormatted}, Dur: {$request->calling_duration}m)",
                        'entries_completed' => 0
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true, 
                'message' => 'Feedback saved successfully!',
                'today_achieved' => $achievedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. DYNAMIC PROVIDER LIST API
    // ==========================================
   public function getAvailableProviders(Request $request)
    {
        $query = DB::table('interested_customers')
            ->where('entry_status', 'active') 
            ->whereNotNull('provider_id')
            ->where('provider_id', '!=', '');

        // 🔥 NAYA: Agar request member portal se aayi hai, to sirf usi member ke providers
        if ($request->filled('member_id')) {
            $query->where('is_member', 1)
                  ->where('member_id', $request->member_id);
        } else {
            $query->whereIn('status', ['Pending', 'pending', 'Pending status', 'General', 'general']);
        }

        $providers = $query->select('provider_id as id', 'provider_name as name')
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $providers
        ]);
    }

    // ==========================================
    // 4. PRINT REPORT FUNCTION
    // ==========================================
    public function printReport(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $compId = 1; 
        $branchId = null; 

        if ($request->filled('company_ids')) {
            $cIds = explode(',', $request->company_ids);
            if (!empty($cIds[0])) $compId = $cIds[0];
        }

        if ($request->filled('branch_ids')) {
            $bIds = explode(',', $request->branch_ids);
            foreach ($bIds as $bId) {
                if (!str_starts_with($bId, 'HO_')) {
                    $branchId = $bId;
                    break;
                }
            }
        }

        $company = \App\Models\Company::find($compId);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

      $query = TelecallerAllocation::with([
            // 🔥 Niche wali line me 'preferred_location' add kiya hai end me
            'customer:id,cust_name,mobile,status,interested_for,refer_by,preferred_location', 
            'task:id,title', 
            'phase:id,phase_name',
            'assignee:id,company_id,branch_id,department_id,designation_id,full_name', 'assignee.company:id,company_name', 
            'assignee.branch:id,branch_name', 
            'assignee.department:id,department_name', 
            'assignee.designation:id,designation_name'
        ]);

        if ($context->is_god) {
            // All Access
        } elseif ($context->is_director) {
            $companyId = $context->company_id;
            $request->merge(['company_ids' => $companyId]);
            $query->whereHas('task', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        } else {
            $request->merge(['assignee_ids' => $user->id]);
            $query->where('assignee_type', get_class($user))
                  ->where('assignee_id', $user->id);
        }

        $compIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
        $branchIds = $request->filled('branch_ids') ? explode(',', $request->branch_ids) : [];
        $deptIds = $request->filled('department_ids') ? explode(',', $request->department_ids) : [];
        $desigIds = $request->filled('designation_ids') ? explode(',', $request->designation_ids) : [];

        if (!empty($compIds) || !empty($branchIds) || !empty($deptIds) || !empty($desigIds)) {
            $query->whereHasMorph('assignee', ['App\Models\Employee', 'App\Models\Member'], function($q, $type) use ($compIds, $branchIds, $deptIds, $desigIds) {
                if (!empty($compIds)) $q->whereIn('company_id', $compIds);
                if (!empty($branchIds)) {
                    $normalBIds = []; $hoCIds = [];
                    foreach ($branchIds as $bId) {
                        if (str_starts_with($bId, 'HO_')) $hoCIds[] = str_replace('HO_', '', $bId);
                        else $normalBIds[] = $bId;
                    }
                    $q->where(function($sq) use ($normalBIds, $hoCIds) {
                        if (count($normalBIds) > 0) $sq->whereIn('branch_id', $normalBIds);
                        if (count($hoCIds) > 0) {
                            $sq->orWhere(function($ssq) use ($hoCIds) {
                                $ssq->whereIn('company_id', $hoCIds)->whereNull('branch_id');
                            });
                        }
                    });
                }
                if (!empty($deptIds)) $q->whereIn('department_id', $deptIds);
                if (!empty($desigIds)) $q->whereIn('designation_id', $desigIds);
            });
        }

        if ($request->filled('assignee_ids')) {
            $query->whereIn('assignee_id', explode(',', $request->assignee_ids));
        }
        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }
        if ($request->filled('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) == 2) {
                $query->whereYear('created_at', $parts[0])->whereMonth('created_at', $parts[1]);
            }
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('preferred_location')) {
            $prefLoc = $request->preferred_location;
            $query->where(function($q) use ($prefLoc) {
                $q->where('preferred_location', 'LIKE', "%{$prefLoc}%")
                  ->orWhereHas('customer', function($cq) use ($prefLoc) {
                      $cq->where('preferred_location', 'LIKE', "%{$prefLoc}%");
                  });
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $query->orderByRaw("FIELD(call_status, 'Pending') DESC")->orderBy('created_at', 'desc');
       $allocations = $query->select('id', 'customer_id', 'task_id', 'phase_id', 'assignee_id', 'assignee_type', 'call_status', 'remark', 'created_at', 'called_at', 'preferred_location')->get(); // 🔥 NAYA ADD KIYA

        return view('employee.print_calling_report', compact('allocations', 'company', 'branch'));
    }

   // ======================================================================
    // 5. SUMMARY DATA ARRAYS (STRICT FULLY SYNCED LOGIC FIXED)
    // ======================================================================
    private function getSummaryDataArray($request, $isPrint = false)
    {
        $empId = ($request->filled('emp_id') && $request->emp_id !== 'undefined' && $request->emp_id !== 'null') ? $request->emp_id : auth()->id();
        $empType = ($request->filled('emp_type') && $request->emp_type !== 'undefined' && $request->emp_type !== 'null') ? $request->emp_type : 'App\\Models\\Employee';
        
        // 🔥 Aapke bataye gaye specifically required columns (koi extra column nahi)
        $query = \App\Models\TelecallerAllocation::select(
            'id', 'customer_id', 'assignee_id', 'assignee_type', 
            'call_status', 'assigned_status', 'remark', 
            'created_at', 'called_at', 'is_rollover'
        )
        ->with(['customer:id,cust_name,mobile,refer_by'])
        ->where('assignee_id', $empId)
        ->where('assignee_type', $empType);

        // ==========================================
        // 🔥 A. INDEPENDENT STATUS FILTER (Strict Match)
        // ==========================================
        $fStatus = $request->filled('call_status') ? trim($request->call_status) : 'all';
        $leadType = $request->filled('lead_type') ? $request->lead_type : 'all';

        if ($fStatus !== 'all') {
            if (in_array(strtolower($fStatus), ['pending', 'pending status'])) {
                $query->whereIn('call_status', ['Pending', 'pending', 'Pending status', ''])
                      ->whereNull('called_at');
                      
                if ($leadType === 'fresh') $query->where('is_rollover', 0);
                elseif ($leadType === 'rollover') $query->where('is_rollover', 1);
            } else {
                $query->where(function($q) use ($fStatus) {
                    if ($fStatus === 'Connected') $q->whereIn('call_status', ['Connected', 'Connected ']);
                    else $q->where('call_status', $fStatus);
                })->whereNotNull('called_at');
            }
        }

        // ==========================================
        // 🔥 B. INDEPENDENT NEW DATE FILTERS
        // ==========================================
        if ($request->filled('followup_date_filter')) {
            $fDate = date('Y-m-d', strtotime($request->followup_date_filter));
            $query->whereDate('followup_date', $fDate);
        }
        if ($request->filled('called_at_filter')) {
            $cDate = date('Y-m-d', strtotime($request->called_at_filter));
            $query->whereDate('called_at', $cDate);
        }

        // ==========================================================
        // 🔥 C. EXACT DATE / MONTH LOGIC (Fixed to whereDate/whereYear)
        // ==========================================================
        $isFilterApplied = $request->boolean('is_filter');
        $filterDate = $request->filled('date') ? date('Y-m-d', strtotime($request->date)) : null;
        $filterMonth = $request->filled('month') ? $request->month : null;

        // Fallback: Agar koi bhi filter na ho toh aaj ki date
        if (!$isFilterApplied && empty($filterDate) && empty($filterMonth) && !$request->filled('followup_date_filter') && !$request->filled('called_at_filter')) {
            $filterDate = now()->toDateString();
        }

        if (!empty($filterDate) || !empty($filterMonth)) {
            $query->where(function ($mainQ) use ($fStatus, $filterDate, $filterMonth) {
                
                $year = $filterMonth ? date('Y', strtotime($filterMonth)) : null;
                $month = $filterMonth ? date('m', strtotime($filterMonth)) : null;

                if (in_array(strtolower($fStatus), ['pending', 'pending status'])) {
                    if ($filterDate) {
                        $mainQ->whereDate('created_at', $filterDate);
                    } elseif ($filterMonth) {
                        $mainQ->whereYear('created_at', $year)
                              ->whereMonth('created_at', $month);
                    }
                } 
                elseif ($fStatus !== 'all') {
                    if ($filterDate) {
                        $mainQ->whereDate('called_at', $filterDate);
                    } elseif ($filterMonth) {
                        $mainQ->whereYear('called_at', $year)
                              ->whereMonth('called_at', $month);
                    }
                } 
                else {
                    if ($filterDate) {
                        $mainQ->where(function($dateQ) use ($filterDate) {
                            $dateQ->whereDate('called_at', $filterDate)
                                  ->orWhere(function($pendingQ) use ($filterDate) {
                                      $pendingQ->whereNull('called_at')
                                               ->whereDate('created_at', $filterDate);
                                  })
                                  ->orWhereDate('followup_date', $filterDate);
                        });
                    } elseif ($filterMonth) {
                        $mainQ->where(function($dateQ) use ($year, $month) {
                            $dateQ->whereYear('called_at', $year)
                                  ->whereMonth('called_at', $month)
                                  ->orWhere(function($pendingQ) use ($year, $month) {
                                      $pendingQ->whereNull('called_at')
                                               ->whereYear('created_at', $year)
                                               ->whereMonth('created_at', $month);
                                  });
                        });
                    }
                }
            });
        }

        $allocations = $query->get();
        $summary = [];
        
        $allStatuses = [
            'Pending', 'Connected', 'Interested', 'Not Interested Call', 'Not Answering Call', 
            'Not Reachable', 'Number Doesn\'t Exists call', 'Site visit Scheduled', 'Site Visit Done Call', 
            'Booking Done', 'Lost Lead', 'Booking Confirm', 'FollowUp Required', 'Registry Completed',
            'On Hold', 'Highly Interested', 'Call Back Requested', 'Busy', 'Switched Off', 
            'DND/Call Rejected', 'Price Discussion', 'Incoming Call Not Available'
        ];

        foreach ($allStatuses as $st) {
            $summary[$st] = ['assigned' => 0, 'called' => 0, 'left' => 0];
        }

        $freshInterestedCustomers = []; 
        $oldInterestedCustomers = []; 
        $othersInterestedCustomers = []; 
        $hotStatuses = ['interested', 'interested call', 'highly interested', 'site visit scheduled', 'site visit scheduled call', 'site visit done', 'site visit done call'];

        foreach ($allocations as $alloc) {
            $rawStatus = trim($alloc->call_status); 
            $status = (empty($rawStatus) || in_array(strtolower($rawStatus), ['pending', 'pending status'])) ? 'Pending' : $rawStatus;

            if (!isset($summary[$status])) {
                $summary[$status] = ['assigned' => 0, 'called' => 0, 'left' => 0];
            }
            
            $summary[$status]['assigned']++;
            
            if (in_array(strtolower($status), ['pending', 'pending status'])) {
                $summary[$status]['left']++;
            } else {
                $summary[$status]['called']++;
            }

            if ($alloc->customer && in_array(strtolower($status), $hotStatuses)) {
                $dataToPush = [
                    'name' => $alloc->customer->cust_name,
                    'mobile' => $alloc->customer->mobile,
                    'refer_by' => $alloc->customer->refer_by ?? 'N/A',
                    'status' => $status,
                    'old_status' => $alloc->assigned_status ?? 'N/A',
                    'remark' => $alloc->remark
                ];

                if ($alloc->is_rollover == 1) {
                    $oldInterestedCustomers[] = $dataToPush;
                } else {
                    $freshInterestedCustomers[] = $dataToPush;
                }
            }
        }

        return [
            'summary' => $summary, 
            'fresh_interested' => $freshInterestedCustomers, 
            'old_interested' => $oldInterestedCustomers,
            'others_interested' => $othersInterestedCustomers, 
            'empId' => $empId, 
            'empType' => $empType
        ];
    }
    public function getSummary(Request $request) { 
        return response()->json(['success' => true, 'data' => $this->getSummaryDataArray($request)['summary']]); 
    }
    
    public function getDetailedSummary(Request $request) {
        $data = $this->getSummaryDataArray($request);
        return response()->json([
            'status' => 'success', 
            'summary' => $data['summary'], 
            'fresh_interested_customers' => $data['fresh_interested'], 
            'old_interested_customers' => $data['old_interested'],      
            'others_interested_customers' => $data['others_interested'] 
        ]);
    }

    public function printSummary(Request $request)
    {
        $data = $this->getSummaryDataArray($request, true);
        $employee = null; $company = null; $branch = null;
        if (class_exists($data['empType'])) {
            $employee = $data['empType']::with(['company', 'branch'])->find($data['empId']);
            if ($employee) { $company = $employee->company; $branch = $employee->branch; }
        }
        return view('admin.telecalling.summary_print', [
            'summary' => $data['summary'], 'employee' => $employee, 
            'company' => $company, 'branch' => $branch, 'request' => $request, 'interestedCustomers' => $data['fresh_interested']
        ]);
    }

    public function fixBlunder(Request $request)
    {
        $mobiles = $request->mobiles;
        
        if (empty($mobiles) || !is_array($mobiles)) {
            return response()->json(['status' => 'error', 'message' => 'No mobiles provided'], 400);
        }

        $customerCount = 0;
        $allocationCount = 0;

        $customers = \App\Models\InterestedCustomer::whereIn('mobile', $mobiles)->get();

        foreach ($customers as $customer) {
            $customer->assigned_telecaller = 'ABDPL-A/0021';
            $customer->save();
            $customerCount++;

            $allocUpdated = TelecallerAllocation::where('customer_id', $customer->id)
                            ->update(['assignee_id' => 33]);
            
            $allocationCount += $allocUpdated;
        }

        return response()->json([
            'status' => 'success',
            'customer_count' => $customerCount,
            'allocation_count' => $allocationCount
        ]);
    }


    public function getTodayStats()
    {
        $today = now()->toDateString();

        // Raw SQL Query to calculate all metrics in a single hit for today's allocations
        $stats = \App\Models\TelecallerAllocation::select(
                'assignee_id',
                'assignee_type',
                DB::raw('COUNT(id) as total_assigned_today'),
                
                // Rollover Counts (Follow-ups, Leftovers, 3-Day Rollovers)
                DB::raw('SUM(CASE WHEN is_rollover = 1 THEN 1 ELSE 0 END) as total_rollover'),
                DB::raw('SUM(CASE WHEN is_rollover = 1 AND call_status LIKE "%Interested%" THEN 1 ELSE 0 END) as rollover_interested'),
                
                // Fresh Leads Counts
                DB::raw('SUM(CASE WHEN is_rollover = 0 THEN 1 ELSE 0 END) as total_fresh'),
                DB::raw('SUM(CASE WHEN is_rollover = 0 AND call_status LIKE "%Interested%" THEN 1 ELSE 0 END) as fresh_interested')
            )
            ->whereDate('created_at', $today)
            ->with(['assignee' => function($q) {
                // Fetching basic details of assignee to show in response
                $q->select('id', 'full_name', 'member_id', 'member_name'); 
            }])
            ->groupBy('assignee_id', 'assignee_type')
            ->get();

        // Format data for clean JSON output
        $formattedStats = $stats->map(function ($stat) {
            $name = $stat->assignee->full_name ?? $stat->assignee->member_name ?? 'Unknown';
            $memberId = $stat->assignee->member_id ?? 'N/A';
            
            return [
                'telecaller_name'      => $name,
                'telecaller_id'        => $memberId,
                'total_assigned_today' => (int) $stat->total_assigned_today,
                'total_rollover'       => (int) $stat->total_rollover,
                'rollover_interested'  => (int) $stat->rollover_interested,
                'total_fresh'          => (int) $stat->total_fresh,
                'fresh_interested'     => (int) $stat->fresh_interested,
            ];
        });

        return response()->json([
            'success' => true,
            'date'    => $today,
            'message' => 'Today\'s Data Distribution by Assignee',
            'data'    => $formattedStats
        ]);
    }
public function debugTodayStats()
    {
        $today = now()->toDateString();

        // Database se aaj ki date ka saara kachha- चिट्ठा (post-mortem)
        $stats = \App\Models\TelecallerAllocation::select(
                'assignee_id',
                'assignee_type',
                DB::raw('COUNT(id) as total_assigned_today'),
                
                // Rollover Math (1)
                DB::raw('SUM(CASE WHEN is_rollover = 1 THEN 1 ELSE 0 END) as total_rollover'),
                DB::raw('SUM(CASE WHEN is_rollover = 1 AND call_status LIKE "%Interested%" THEN 1 ELSE 0 END) as rollover_interested'),
                
                // Fresh Leads Math (0)
                DB::raw('SUM(CASE WHEN is_rollover = 0 THEN 1 ELSE 0 END) as total_fresh'),
                DB::raw('SUM(CASE WHEN is_rollover = 0 AND call_status LIKE "%Interested%" THEN 1 ELSE 0 END) as fresh_interested')
            )
            ->whereDate('created_at', $today)
            ->with(['assignee' => function($q) {
                // Name aur ID lane ke liye
                $q->select('id', 'full_name', 'member_id'); 
            }])
            ->groupBy('assignee_id', 'assignee_type')
            ->get();

        // Seedha Controller se HTML return kar rahe hain temporary view ke liye
        $html = '<!DOCTYPE html><html><head><title>Today Allocation Stats</title>';
        $html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        $html .= '</head><body class="bg-light p-5">';
        $html .= '<div class="container bg-white p-4 rounded shadow-lg">';
        $html .= '<h3 class="fw-bold text-dark mb-4 border-bottom pb-2">🎯 Allocation Post-Mortem (Date: '.$today.')</h3>';
        $html .= '<table class="table table-bordered table-hover text-center align-middle">';
        $html .= '<thead class="table-dark">
                    <tr>
                        <th class="text-start">Telecaller Name</th>
                        <th>Member ID</th>
                        <th class="text-primary">Total Assigned</th>
                        <th class="text-warning">Total Rollover (1)</th>
                        <th class="text-success">Rollover Interested</th>
                        <th class="text-info">Total Fresh (0)</th>
                        <th class="text-success">Fresh Interested</th>
                    </tr>
                  </thead><tbody>';

        if($stats->isEmpty()) {
            $html .= '<tr><td colspan="7" class="text-danger fw-bold py-4">Aaj ('.$today.') koi bhi task allocate nahi hua hai!</td></tr>';
        }

        foreach($stats as $stat) {
            $name = $stat->assignee->full_name ?? $stat->assignee->member_name ?? 'Unknown';
            $memberId = $stat->assignee->member_id ?? 'N/A';
            
            $html .= "<tr>
                        <td class='text-start fw-bold text-secondary'>{$name}</td>
                        <td><span class='badge bg-secondary'>{$memberId}</span></td>
                        <td class='fw-bold fs-5 text-primary bg-light'>{$stat->total_assigned_today}</td>
                        <td class='fw-bold fs-5 text-warning'>{$stat->total_rollover}</td>
                        <td class='fw-bold fs-5 text-success'>{$stat->rollover_interested}</td>
                        <td class='fw-bold fs-5 text-info bg-light'>{$stat->total_fresh}</td>
                        <td class='fw-bold fs-5 text-success bg-light'>{$stat->fresh_interested}</td>
                      </tr>";
        }

        $html .= '</tbody></table></div></body></html>';

        return response($html);
    }

    // ======================================================================
    // ONE-TIME SYNC: Purane Data ko naye 'is_assigned' model par shift karna
    // ======================================================================
    public function syncLegacyData()
    {
        // Data zyada ho sakta hai, isliye script timeout badha rahe hain
        set_time_limit(0); 

        // Har customer ki sirf sabse LATEST allocation uthayenge
        $allocations = \App\Models\TelecallerAllocation::select('customer_id', 'assignee_id', 'assignee_type')
            ->whereIn('id', function ($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                      ->from('telecaller_allocations')
                      ->groupBy('customer_id');
            })
            ->get();

        $updateCount = 0;

        foreach ($allocations as $alloc) {
            $memberId = null;

            // Assignee (Employee/Member) ki table se member_id nikalna
            if (class_exists($alloc->assignee_type)) {
                $assignee = $alloc->assignee_type::find($alloc->assignee_id);
                if ($assignee) {
                    $memberId = $assignee->member_id ?? null;
                }
            }

            // Data prepare karna
            $updateData = ['is_assigned' => 1];
            
            if ($memberId) {
                $updateData['assigned_telecaller'] = $memberId;
            }

            // interested_customers table me master update
            \App\Models\InterestedCustomer::where('id', $alloc->customer_id)
                ->update($updateData);

            $updateCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Jadoo ho gaya bhai! Total {$updateCount} purane customers ka data successfully sync aur update ho gaya hai.",
        ]);
    }

}