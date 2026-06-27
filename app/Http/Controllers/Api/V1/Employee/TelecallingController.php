<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelecallerAllocation;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TelecallingController extends Controller
{
    public function getAllocations(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 🔥 Excel aur UI ke liye saari relations load kar li hain (Company, Branch, Dept, Desig)
        $query = TelecallerAllocation::with([
            'customer',
            'task',
            'phase',
            'assignee',
            'assignee.company',
            'assignee.branch',
            'assignee.department',
            'assignee.designation'
        ]);

        // ==========================================
        // 🔥 1. RBAC SECURITY SCOPE 🔥
        // ==========================================
        if ($context->is_god) {
            // God ko sab dikhega
        } elseif ($context->is_director) {
            // Director ko sirf apni company dikhegi
            $companyId = $context->company_id;
            $request->merge(['company_ids' => $companyId]); // Force filter
            $query->whereHas('task', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        } else {
            // Employee ko sirf apna data dikhega
            $request->merge(['assignee_ids' => $user->id]); // Force filter
            $query->where('assignee_type', get_class($user))
                ->where('assignee_id', $user->id);
        }

        // ==========================================
        // 🔥 2. MULTI-SELECT FILTERS (Hierarchy) 🔥
        // ==========================================
        $compIds = $request->filled('company_ids') ? explode(',', $request->company_ids) : [];
        $branchIds = $request->filled('branch_ids') ? explode(',', $request->branch_ids) : [];
        $deptIds = $request->filled('department_ids') ? explode(',', $request->department_ids) : [];
        $desigIds = $request->filled('designation_ids') ? explode(',', $request->designation_ids) : [];

        // Agar inme se koi bhi filter laga hai, toh Assignee (Employee/Member) ke table me jhank kar filter karenge
        if (!empty($compIds) || !empty($branchIds) || !empty($deptIds) || !empty($desigIds)) {
            $query->whereHasMorph('assignee', ['App\Models\Employee', 'App\Models\Member'], function ($q, $type) use ($compIds, $branchIds, $deptIds, $desigIds) {
                if (!empty($compIds)) $q->whereIn('company_id', $compIds);

                if (!empty($branchIds)) {
                    $normalBIds = [];
                    $hoCIds = [];
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

        // Specific Telecaller IDs (Multi-select)
        if ($request->filled('assignee_ids')) {
            $query->whereIn('assignee_id', explode(',', $request->assignee_ids));
        }

        // Status Filter
        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }

        // Month Filter (YYYY-MM)
        if ($request->filled('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) == 2) {
                $query->whereYear('created_at', $parts[0])
                    ->whereMonth('created_at', $parts[1]);
            }
        }

        // Date Filter (YYYY-MM-DD)
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Live Search Filter (Customer Name ya Mobile)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('cust_name', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $query->orderByRaw("FIELD(call_status, 'Pending') DESC")->orderBy('created_at', 'desc');

        // ==========================================
        // 🔥 3. EXPORT VS PAGINATION 🔥
        // ==========================================
        if ($request->export == 1) {
            $allocations = $query->get();
            return response()->json(['success' => true, 'data' => $allocations]);
        }

        $limit = 20;
        $offset = $request->offset ?? 0;
        $total = $query->count();

        $allocations = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $allocations,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]);
    }

    public function updateFeedback(Request $request, $id)
    {
        $request->validate([
            'call_status' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $allocation = TelecallerAllocation::with('customer')->findOrFail($id);
            $user = auth()->user();

            $wasPending = ($allocation->call_status === 'Pending');

            // 1. Allocation Table Update (Sirf tracking wali fields)
            $allocation->update([
                'call_status'      => $request->call_status,
                'interested_for'   => $request->interested_for,
                'budget'           => $request->budget,
                'followup_date'    => $request->followup_date,
                'followup_month'   => $request->followup_month,
                'dob'              => $request->dob,
                'anniversary_date' => $request->anniversary_date,
                'remark'           => $request->remark,
                'called_at'        => now(),
            ]);

            // 2. Main Customer Table Update (Sari fields yahan save hongi)
            if ($allocation->customer) {
                $allocation->customer->update([
                    'alternate_no'        => $request->alternate_no, // 🔥 NEW
                    'email'               => $request->email,        // 🔥 NEW
                    'address'             => $request->address,      // 🔥 NEW
                    'dob'                 => $request->dob,          // 🔥 NEW
                    'anniversary_date'    => $request->anniversary_date, // 🔥 NEW
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

            // 3. Task Achieved Count Logic
            if ($wasPending && $request->call_status !== 'Pending') {
                $task = Task::find($allocation->task_id);
                if ($task) {
                    $task->increment('achieved_count');
                    if ($task->achieved_count >= $task->target_count && $task->status !== 'Completed') {
                        $task->update(['status' => 'Completed']);
                    } elseif ($task->status === 'Pending') {
                        $task->update(['status' => 'In-Progress']);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Feedback saved successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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



}
