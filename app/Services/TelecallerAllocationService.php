<?php

namespace App\Services;

use App\Models\InterestedCustomer;
use App\Models\TelecallerAllocation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelecallerAllocationService
{
    // 🔥 THE 3 MASTER CATEGORIES 🔥[cite: 14]
    // 1. Blacklist: Inhe dobara KABHI assign nahi karna hai[cite: 14]
    protected $blacklistStatuses = [
        'Number Doesn\'t Exists call',
        'Number Does Not exists',
        'Site Visit Done Call',
        'Site Visit Done',
        'Booking Done',
        'Booking Confirm',
        'Lost',
        'Lost Lead',
        'Not Interested',
        'Not Interested Call',
        'Registry Completed',
        'registry Done'
    ];

    // 2. Rollover: 3 din tak retry karne wale status[cite: 14]
    protected $rolloverStatuses = [
        'Busy',
        'Switch Off',
        'Switched Off',
        'DND/Call Rejected',
        'DND/Call Restricted',
        'Not Reachable',
        'Not Reachable call',
        'Not Answering',
        'Not Answering Call',
        'Incoming Call Not Available'
    ];

    // 3. Priority/Scheduled: Sabse pehle assign honge[cite: 14]
    protected $priorityStatuses = [
        'Follow Up',
        'Interested',
        'Highly Interested',
        'Connected',
        'Connected ',
        'Call Back Requested',
        'On Hold'
    ];

    /**
     * ========================================================
     * 🔥 NAYA HELPER: CUMULATIVE REMARKS HISTORY LAANE KE LIYE
     * ========================================================
     */
    private function getCustomerRemarkHistory($customerId)
    {
        // Allocation table se picchli successful calls nikalenge
        $historyRecords = TelecallerAllocation::where('customer_id', $customerId)
            ->whereNotNull('called_at')
            ->whereNotNull('remark')
            ->orderBy('called_at', 'desc')
            ->take(3) // Last 3 interactions ki limit taaki box bohot bada na ho jaye
            ->get();

        if ($historyRecords->isEmpty()) {
            // Agar allocation me nahi hai, toh master table se last remark utha lo
            $cust = InterestedCustomer::find($customerId);
            return ($cust && $cust->remark) ? "\n--- History ---\n[Prev]: " . $cust->remark : "";
        }

        $historyText = "\n\n--- History ---";
        foreach ($historyRecords as $rec) {
            $date = Carbon::parse($rec->called_at)->format('d-M-y');
            // Purane system tags (Priority/Rollover) filter karke hata denge
            $cleanRemark = preg_replace('/^(🔥|⚠️|⏰|✨)\s*\[.*?\]\s*/', '', $rec->remark);
            $historyText .= "\n[{$date} | {$rec->call_status}]: {$cleanRemark}";
        }
        return $historyText;
    }

    /**
     * ========================================================
     * CATEGORY 1: PRIORITY & SCHEDULED (Adjusted in 250 Target)[cite: 14]
     * ========================================================
     */
    public function allocatePriorityCustomers($task)
    {
        $today = now()->toDateString();

        $priorityCustomers = InterestedCustomer::where('assigned_telecaller', $task->assignee_id)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', $this->priorityStatuses)
                    ->orWhere(function ($subQ) use ($today) {
                        $subQ->whereNotNull('followup_date')
                            ->whereDate('followup_date', '<=', $today);
                    });
            })->get();

        $allocatedCount = 0;

        foreach ($priorityCustomers as $customer) {
            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$alreadyToday) {
                // 🔥 NAYA: History attach karo
                $history = $this->getCustomerRemarkHistory($customer->id);

                $remark = "🔥 [Priority] Status: {$customer->status}";
                if ($customer->status === 'On Hold' && $customer->updated_at) {
                    $remark = "⚠️ [Scheduled] Last status was 'On Hold' on " . $customer->updated_at->format('d-M-Y');
                } elseif ($customer->followup_date && $customer->followup_date <= $today) {
                    $remark = "⏰ [Follow-Up] Scheduled for: " . Carbon::parse($customer->followup_date)->format('d-M-Y');
                }

                $remark .= $history; // Attach history here

                TelecallerAllocation::create([
                    'task_id'       => $task->id,
                    'phase_id'      => $task->phase_id,
                    'customer_id'   => $customer->id,
                    'assignee_type' => $task->assignee_type,
                    'assignee_id'   => $task->assignee_id,
                    'call_status'   => 'Pending',
                    'remark'        => $remark,
                ]);
                $allocatedCount++;
            }
        }

        return $allocatedCount;
    }

    public function allocateFreshCustomers($task, $targetCount)
    {
        if ($targetCount <= 0 || !$task->phase_id) return 0;
        $allocatedCount = 0;
        $today = now()->toDateString();

        // ==========================================
        // 1. TODAY'S FOLLOW UPS (Strictly for this Telecaller)[cite: 14]
        // ==========================================
        $myFollowUpCustomerIds = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->whereDate('followup_date', $today)
            ->pluck('customer_id')
            ->toArray();

        if (!empty($myFollowUpCustomerIds)) {
            $followUpQuery = InterestedCustomer::whereIn('id', $myFollowUpCustomerIds);

            if ($task->company_id) $followUpQuery->where('company_id', $task->company_id);
            if ($task->branch_id) $followUpQuery->where('branch_id', $task->branch_id);

            $alreadyAssignedTodayIds = TelecallerAllocation::where('assignee_id', $task->assignee_id)
                ->whereDate('created_at', $today)
                ->pluck('customer_id')->toArray();

            $followUpQuery->whereNotIn('id', $alreadyAssignedTodayIds);

            $priorityCustomers = $followUpQuery->limit($targetCount)->get();

            foreach ($priorityCustomers as $customer) {
                // 🔥 NAYA: History attach karo
                $history = $this->getCustomerRemarkHistory($customer->id);

                TelecallerAllocation::create([
                    'task_id'         => $task->id,
                    'phase_id'        => $task->phase_id,
                    'customer_id'     => $customer->id,
                    'assignee_type'   => $task->assignee_type,
                    'assignee_id'     => $task->assignee_id,
                    'call_status'     => 'Pending',
                    'assigned_status' => $customer->status,
                    'remark'          => '⚠️ [Today FollowUp] Last Status: ' . $customer->status . $history,
                ]);
                $allocatedCount++;
            }
        }

        // ==========================================
        // 2. FRESH PENDING LEADS[cite: 14]
        // ==========================================
        $remainingTarget = $targetCount - $allocatedCount;

        if ($remainingTarget > 0) {
            $alreadyAssignedPhaseIds = TelecallerAllocation::where('phase_id', $task->phase_id)
                ->pluck('customer_id')->toArray();

            $freshQuery = InterestedCustomer::whereIn('status', ['Pending', 'pending', 'Pending status'])
                ->whereNotIn('id', $alreadyAssignedPhaseIds);

            if ($task->company_id) $freshQuery->where('company_id', $task->company_id);
            if ($task->branch_id) $freshQuery->where('branch_id', $task->branch_id);

            $freshCustomers = $freshQuery->limit($remainingTarget)->get();

            foreach ($freshCustomers as $customer) {
                TelecallerAllocation::create([
                    'task_id'         => $task->id,
                    'phase_id'        => $task->phase_id,
                    'customer_id'     => $customer->id,
                    'assignee_type'   => $task->assignee_type,
                    'assignee_id'     => $task->assignee_id,
                    'call_status'     => 'Pending',
                    'assigned_status' => 'Pending',
                    'remark'          => '✨ [Fresh Lead] Never Called Before.',
                ]);
                $allocatedCount++;
            }
        }

        return $allocatedCount;
    }

    /**
     * ========================================================
     * CATEGORY 3: THE 3-DAY ROLLOVER (Target ke upar EXTRA)[cite: 14]
     * ========================================================
     */
    public function allocateRolloverCustomers($task)
    {
        $allocatedCount = 0;
        $today = now()->toDateString();

        $recentAllocations = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->whereIn('call_status', $this->rolloverStatuses)
            ->whereDate('created_at', now()->subDays(1)->toDateString())
            ->latest()
            ->get()
            ->unique('customer_id');

        foreach ($recentAllocations as $alloc) {
            $customer = $alloc->customer;

            if (!$customer || in_array($customer->status, $this->blacklistStatuses) || in_array($customer->status, $this->priorityStatuses)) {
                continue;
            }

            $badStatusCount = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereIn('call_status', $this->rolloverStatuses)
                ->count();

            if ($badStatusCount < 3) {
                $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$alreadyToday) {
                    // 🔥 NAYA: History attach karo
                    $history = $this->getCustomerRemarkHistory($customer->id);

                    TelecallerAllocation::create([
                        'task_id'       => $task->id,
                        'phase_id'      => $task->phase_id,
                        'customer_id'   => $customer->id,
                        'assignee_type' => $task->assignee_type,
                        'assignee_id'   => $task->assignee_id,
                        'call_status'   => 'Pending',
                        'remark'        => '⚠️ [Rollover] ' . $customer->status . ' (Attempt ' . ($badStatusCount + 1) . ' of 3).' . $history,
                    ]);
                    $allocatedCount++;
                }
            }
        }

        return $allocatedCount;
    }

    /**
     * ========================================================
     * YESTERDAY'S PENDING LEFTOVER (Jo kal chhut gaye the)[cite: 14]
     * ========================================================
     */
    public function allocatePendingLeftovers($task)
    {
        $allocatedCount = 0;

        $recentAllocations = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->where('call_status', 'Pending')
            ->where('task_id', '!=', $task->id)
            ->whereDate('created_at', now()->subDays(1)->toDateString())
            ->latest()
            ->get()
            ->unique('customer_id');

        foreach ($recentAllocations as $alloc) {
            $customer = $alloc->customer;

            if (!$customer || $customer->status !== 'Pending') {
                continue;
            }

            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (!$alreadyToday) {
                // 🔥 NAYA: History attach karo
                $history = $this->getCustomerRemarkHistory($customer->id);

                TelecallerAllocation::create([
                    'task_id'       => $task->id,
                    'phase_id'      => $task->phase_id,
                    'customer_id'   => $customer->id,
                    'assignee_type' => $task->assignee_type,
                    'assignee_id'   => $task->assignee_id,
                    'call_status'   => 'Pending',
                    'remark'        => '⚠️ [Leftover] Kal call nahi hui thi.' . $history,
                ]);
                $allocatedCount++;
            }
        }

        return $allocatedCount;
    }
}
