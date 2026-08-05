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
    // 1. GET ALLOCATIONS (Speed Optimized & 'Pending' Variations Fixed)
    // ======================================================================
  public function getAllocations(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $query = \App\Models\TelecallerAllocation::with([
            'customer', 'task', 'phase', 'assignee', 
            'assignee.company', 'assignee.branch', 'assignee.department', 'assignee.designation'
        ]);

        // ==========================================
        // 1. RBAC SECURITY SCOPE
        // ==========================================
        if ($context->is_god) {
            // God ko sab dikhega
        } elseif ($context->is_director) {
            $companyId = $context->company_id;
            $request->merge(['company_ids' => $companyId]);
            $query->whereHas('task', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        } else {
            $request->merge(['assignee_ids' => $user->id]);
            $query->where('assignee_type', get_class($user))->where('assignee_id', $user->id);
        }

        // ==========================================
        // 2. MULTI-SELECT FILTERS
        // ==========================================
        $compIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
        $branchIds = $request->filled('branch_ids') ? explode(',', $request->branch_ids) : [];
        $deptIds = $request->filled('department_ids') ? explode(',', $request->department_ids) : [];
        $desigIds = $request->filled('designation_ids') ? explode(',', $request->designation_ids) : [];

        if (!empty($compIds) || !empty($branchIds) || !empty($deptIds) || !empty($desigIds)) {
            $query->whereHasMorph('assignee', ['App\Models\Employee', 'App\Models\Member'], function ($q) use ($compIds, $branchIds, $deptIds, $desigIds) {
                if (!empty($compIds)) $q->whereIn('company_id', $compIds);
                if (!empty($branchIds)) {
                    $normalBIds = []; $hoCIds = [];
                    foreach ($branchIds as $bId) {
                        if (str_starts_with($bId, 'HO_')) $hoCIds[] = str_replace('HO_', '', $bId);
                        else $normalBIds[] = $bId;
                    }
                    $q->where(function ($sq) use ($normalBIds, $hoCIds) {
                        if (count($normalBIds) > 0) $sq->whereIn('branch_id', $normalBIds);
                        if (count($hoCIds) > 0) {
                            $sq->orWhere(function ($ssq) use ($hoCIds) {
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

        // 🔥 STATUS FILTER (Fix: Handling all variations of Pending)
        if ($request->filled('call_status')) {
            $fStatus = $request->call_status;
            if (in_array(strtolower($fStatus), ['pending', 'pending status'])) {
                $query->whereIn('call_status', ['Pending', 'pending', 'Pending status']);
            } else {
                $query->where('call_status', $fStatus);
            }
        }

        // 🔥 MONTH & DATE FILTER (Assigned OR Actioned)
        if ($request->filled('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) == 2) {
                $year = $parts[0]; $month = $parts[1];
                $query->where(function ($q) use ($year, $month) {
                    $q->where(function ($sq) use ($year, $month) {
                        $sq->whereYear('created_at', $year)->whereMonth('created_at', $month);
                    })->orWhere(function ($sq) use ($year, $month) {
                        $sq->whereYear('called_at', $year)->whereMonth('called_at', $month);
                    });
                });
            }
        }

        if ($request->filled('date')) {
            $filterDate = $request->date;
            $query->where(function ($q) use ($filterDate) {
                $q->whereDate('created_at', $filterDate)->orWhereDate('called_at', $filterDate);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        // ==========================================
        // 🔥 3. "TODAY'S SCHEDULED" FILTER 🔥
        // ==========================================
        if ($request->scheduled_today == 1) {
            $today = now()->toDateString();
            $todayMonthDay = now()->format('m-d');
            $query->where(function($q) use ($today, $todayMonthDay) {
                // Table ka followup date check karein
                $q->whereDate('followup_date', $today)
                  // Customer ka dob/anniversary check karein
                  ->orWhereHas('customer', function($cq) use ($todayMonthDay) {
                      $cq->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$todayMonthDay])
                         ->orWhereRaw("DATE_FORMAT(anniversary_date, '%m-%d') = ?", [$todayMonthDay]);
                  })
                  // Priority remark match karein
                  ->orWhere('remark', 'LIKE', '%[Today FollowUp]%')
                  ->orWhere('remark', 'LIKE', '%[Scheduled]%');
            });
        }

        // ==========================================
        // 🔥 4. SMART SORTING & PAGINATION TIE-BREAKER 🔥
        // ==========================================
        $query->orderByRaw("
            CASE 
                WHEN remark LIKE '%[Today FollowUp]%' OR remark LIKE '%[Priority]%' OR remark LIKE '%[Scheduled]%' THEN 1
                WHEN remark LIKE '%[Rollover]%' THEN 2
                WHEN remark LIKE '%[Leftover]%' THEN 3
                WHEN remark LIKE '%[Fresh Lead]%' OR remark LIKE '%[Fresh Assigned]%' THEN 4
                ELSE 5
            END ASC
        ")
        ->orderByRaw("FIELD(call_status, 'Pending status', 'Pending') DESC")
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc'); // 🔥 TIE-BREAKER: Load More ko fail nahi hone dega

        // Export Bypass
        if ($request->export == 1) {
            return response()->json(['success' => true, 'data' => $query->get()]);
        }

        // ==========================================
        // 🔥 5. PAGINATION & PERFORMANCE BOOST 🔥
        // ==========================================
        $limit = 20;
        $offset = $request->offset ?? 0;
        
        $totalCount = 0;
        $employeeSummary = [];

        // Hum sirf pehli baar (jab offset 0 ho) total count nikalenge 
        // aur employee ka header summary banayenge taaki speed fast ho
        if ($offset == 0) {
            $totalCount = $query->count();
            
            $summaryQuery = clone $query;
            $summaryQuery->getQuery()->orders = null; // Heavy sorting hatao taki Group By fast chale
            
            $employeeSplit = $summaryQuery->select('assignee_id', 'assignee_type', \DB::raw('count(*) as count'))
                ->groupBy('assignee_id', 'assignee_type')->get();

            foreach ($employeeSplit as $split) {
                if ($empClass = $split->assignee_type) {
                    if (class_exists($empClass) && $empRecord = $empClass::find($split->assignee_id)) {
                        $employeeSummary[] = [
                            'id' => $split->assignee_id,
                            'type' => $split->assignee_type,
                            'name' => $empRecord->full_name ?? $empRecord->member_name ?? 'Unknown',
                            'count' => $split->count
                        ];
                    }
                }
            }
        }

        // Niche ka data sirf limit ke hisab se 20-20 karke uthega (FAST)
        $data = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'has_more' => count($data) === $limit,
            'total_count' => $totalCount, // UI pehle load pe isko read karega
            'employee_summary' => $employeeSummary
        ]);
    }
  // ======================================================================
    // 2. UPDATE FEEDBACK & PROGRESS BAR (251/250 Logic)
    // ======================================================================
    public function updateFeedback(Request $request, $id)
    {
        $request->validate(['call_status' => 'required|string']);

        \DB::beginTransaction();
        try {
            $allocation = \App\Models\TelecallerAllocation::with('customer')->findOrFail($id);
            $user = auth()->user();

            $allocation->update([
                'call_status'      => $request->call_status,
                'interested_for'   => $request->interested_for,
                'budget'           => $request->budget,
                'followup_date'    => $request->followup_date,
                'followup_month'   => $request->followup_month,
                'dob'              => $request->dob,
                'anniversary_date' => $request->anniversary_date,
                'remark'           => $request->remark,
                'called_at'        => now(), // 🔥 Progress bar isi se chalega
            ]);

            if ($allocation->customer) {
                $allocation->customer->update([
                    'alternate_no'        => $request->alternate_no, 
                    'email'               => $request->email,        
                    'address'             => $request->address,      
                    'dob'                 => $request->dob,          
                    'anniversary_date'    => $request->anniversary_date, 
                    'status'              => $request->call_status,
                    'budget'              => $request->budget,
                    'interested_for'      => $request->interested_for,
                    'followup_date'       => $request->followup_date,
                    'followup_month'      => $request->followup_month,
                    'remark'              => $request->remark,
                    'called_by'           => $user->name ?? $user->full_name ?? 'Telecaller',
                    'assigned_telecaller' => $user->member_id ?? $user->id,
                ]);
            }

            // 🔥 SMART PROGRESS TRACKING LOGIC (251/250)
            $today = now()->toDateString();
            $achievedCount = \App\Models\TelecallerAllocation::where('assignee_id', $allocation->assignee_id)
                ->where('assignee_type', $allocation->assignee_type)
                ->whereDate('called_at', $today)
                ->whereNotIn('call_status', ['Pending', 'pending', 'Pending status'])
                ->count();

            $todayTask = \App\Models\Task::where('assignee_id', $allocation->assignee_id)
                ->where('assignee_type', $allocation->assignee_type)
                ->whereDate('created_at', $today)
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
                        'message_or_remark' => "🎉 System Note: Target Achieved! Total calls updated today: {$achievedCount}.",
                        'entries_completed' => 0
                    ]);
                } elseif ($todayTask->status === 'Pending' && $achievedCount > 0) {
                    $todayTask->status = 'In-Progress';
                }
                $todayTask->save();
            }

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Feedback saved successfully!']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

// ==========================================
    // 🔥 DYNAMIC PROVIDER LIST API (FIXED) 🔥
    // ==========================================
    public function getAvailableProviders()
    {
        // 🔥 FIX: Eloquent ki jagah seedha DB facade use kiya hai taaki 'Pro_01' 0 na ban jaye
        $providers = \Illuminate\Support\Facades\DB::table('interested_customers')
            ->whereIn('status', ['Pending', 'pending', 'Pending status', 'General', 'general'])
            ->where('entry_status', 'active') 
            ->whereNotNull('provider_id')
            ->where('provider_id', '!=', '')
            ->select('provider_id as id', 'provider_name as name')
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $providers
        ]);
    }


    // ==========================================
    // 🔥 PRINT REPORT FUNCTION 🔥
    // ==========================================
    public function printReport(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 1. Determine Company & Branch for Header Component
        $compId = 1; // Default Company
        $branchId = null; // Default Branch (HO)

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

        // 2. Fetch Data (Same exact logic as Export)
        $query = TelecallerAllocation::with([
            'customer', 'task', 'phase', 
            'assignee', 'assignee.company', 'assignee.branch', 'assignee.department', 'assignee.designation'
        ]);

        // RBAC Security Scope
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

        // Multi-Select Filters
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
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $query->orderByRaw("FIELD(call_status, 'Pending') DESC")->orderBy('created_at', 'desc');
        $allocations = $query->get();

        // 3. Return to Blade View (We will create this in Step 2)
        return view('employee.print_calling_report', compact('allocations', 'company', 'branch'));
    }
// ======================================================================
    // 3 & 4 & 5. MODAL SUMMARY & PRINT SUMMARY & REPORT
    // ======================================================================
    private function getSummaryDataArray($request, $isPrint = false)
    {
        $empId = ($request->filled('emp_id') && $request->emp_id !== 'undefined' && $request->emp_id !== 'null') ? $request->emp_id : auth()->id();
        $empType = ($request->filled('emp_type') && $request->emp_type !== 'undefined' && $request->emp_type !== 'null') ? $request->emp_type : 'App\\Models\\Employee';
        
        $query = \App\Models\TelecallerAllocation::with(['customer'])->where('assignee_id', $empId)->where('assignee_type', $empType);

        $filterStatus = $request->call_status;
        if ($request->filled('call_status')) {
            $query->where(function($q) use ($filterStatus) {
                if (in_array(strtolower($filterStatus), ['pending', 'pending status'])) {
                    $q->whereIn('call_status', ['Pending', 'pending', 'Pending status']);
                } else {
                    $q->where('call_status', $filterStatus);
                }
            });
        }
        
        if ($request->filled('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) == 2) {
                $query->where(function ($q) use ($parts) {
                    $q->where(function ($sq) use ($parts) {
                        $sq->whereYear('created_at', $parts[0])->whereMonth('created_at', $parts[1]);
                    })->orWhere(function ($sq) use ($parts) {
                        $sq->whereYear('called_at', $parts[0])->whereMonth('called_at', $parts[1]);
                    });
                });
            }
        }
        if ($request->filled('date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('created_at', $request->date)->orWhereDate('called_at', $request->date);
            });
        }

        $allocations = $query->get();
        
        $allStatuses = [
            'Pending', 'Connected', 'Interested', 'Not Interested Call', 'Not Answering Call', 
            'Not Reachable', 'Number Doesn\'t Exists call', 'Site visit Scheduled', 'Site Visit Done Call', 
            'Booking Done', 'Lost Lead', 'Booking Confirm', 'FollowUp Required', 'Registry Completed',
            'On Hold', 'Highly Interested', 'Call Back Requested', 'Busy', 'Switched Off', 
            'DND/Call Rejected', 'Price Discussion', 'Incoming Call Not Available'
        ];
        
        $summary = [];
        foreach ($allStatuses as $st) {
            $summary[$st] = ['assigned' => 0, 'called' => 0, 'left' => 0];
        }

        $interestedCustomers = []; 

        foreach ($allocations as $alloc) {
            $rawStatus = $alloc->call_status;
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

            if ($alloc->customer && in_array(strtolower($alloc->customer->status), ['interested', 'highly interested'])) {
                $interestedCustomers[] = [
                    'name' => $alloc->customer->cust_name,
                    'mobile' => $alloc->customer->mobile,
                    'refer_by' => $alloc->customer->refer_by ?? 'N/A',
                    'status' => $alloc->customer->status,
                ];
            }
        }
        return ['summary' => $summary, 'interested' => $interestedCustomers, 'empId' => $empId, 'empType' => $empType];
    }

    public function getSummary(Request $request) { return response()->json(['success' => true, 'data' => $this->getSummaryDataArray($request)['summary']]); }
    
    public function getDetailedSummary(Request $request) {
        $data = $this->getSummaryDataArray($request);
        return response()->json(['status' => 'success', 'summary' => $data['summary'], 'interested_customers' => $data['interested']]);
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
            'company' => $company, 'branch' => $branch, 'request' => $request, 'interestedCustomers' => $data['interested']
        ]);
    }
}
