<?php

namespace App\Services;

use App\Models\InterestedCustomer;
use App\Models\TelecallerAllocation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelecallerAllocationService
{
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

    protected $priorityStatuses = [
        'Follow Up',
        'Interested',
        'Highly Interested',
        'Connected',
        'Connected ',
        'Call Back Requested',
        'On Hold'
    ];

    private function getCustomerRemarkHistory($customerId)
    {
        $historyRecords = TelecallerAllocation::where('customer_id', $customerId)
            ->whereNotNull('called_at')
            ->whereNotNull('remark')
            ->orderBy('called_at', 'desc')
            ->take(3)
            ->get();

        if ($historyRecords->isEmpty()) {
            $cust = InterestedCustomer::find($customerId);
            return ($cust && $cust->remark) ? "\n--- History ---\n[Prev]: " . $cust->remark : "";
        }

        $historyText = "\n\n--- History ---";
        foreach ($historyRecords as $rec) {
            $date = Carbon::parse($rec->called_at)->format('d-M-y');
            $cleanRemark = explode("\n--- History ---", $rec->remark)[0];
            $cleanRemark = preg_replace('/^(🔥|⚠️|⏰|✨|🚨)\s*\[.*?\]\s*/', '', $cleanRemark);
            $historyText .= "\n[{$date} | {$rec->call_status}]: " . trim($cleanRemark);
        }
        return $historyText;
    }

    /**
     * YESTERDAY'S LEFTOVER PENDING (Jinka called_at null hai)
     */
    public function allocatePendingLeftovers($task)
    {
        $allocatedCount = 0;
        $today = now()->toDateString();

        $recentAllocations = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->whereNull('called_at') // 🔥 STRICT CHECK: Kal jinhe call nahi kiya gaya
            ->where('task_id', '!=', $task->id)
            ->whereDate('created_at', now()->subDays(1)->toDateString())
            ->latest()
            ->get()
            ->unique('customer_id');

        foreach ($recentAllocations as $alloc) {
            $customer = $alloc->customer;
            if (!$customer) continue;

            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$alreadyToday) {
                $history = $this->getCustomerRemarkHistory($customer->id);
                TelecallerAllocation::create([
                    'task_id'         => $task->id,
                    'phase_id'        => $task->phase_id,
                    'customer_id'     => $customer->id,
                    'assignee_type'   => $task->assignee_type,
                    'assignee_id'     => $task->assignee_id,
                    'call_status'     => 'Pending',
                    'assigned_status' => $customer->status,
                    'remark'          => '⚠️ [Leftover] Kal call nahi hui thi.' . $history,
                    'is_rollover'     => 1, // 🔥 ROLLOVER 1 JAYEGA
                ]);
                $allocatedCount++;
            }
        }
        return $allocatedCount;
    }

    /**
     * CATEGORY 1: FOLLOW-UPS / PRIORITY
     */
    public function allocatePriorityCustomers($task, $targetCount)
    {
        if ($targetCount <= 0) return 0;

        $today = now()->toDateString();
        $assigneeRecord = $task->assignee_type::find($task->assignee_id);
        if (!$assigneeRecord) return 0;

        $telecallerId = $assigneeRecord->member_id ?? $task->assignee_id;

        $priorityCustomers = InterestedCustomer::where('assigned_telecaller', $telecallerId)
            ->where(function ($q) use ($today) {
                $q->where(function ($subQ1) use ($today) {
                    $subQ1->whereIn('status', $this->priorityStatuses)
                        ->whereDate('followup_date', '<=', $today);
                })
                    ->orWhere(function ($subQ2) use ($today) {
                        $subQ2->whereNotNull('followup_date')
                            ->whereDate('followup_date', '<=', $today);
                    });
            })->get();

        $allocatedCount = 0;

        foreach ($priorityCustomers as $customer) {
            if ($allocatedCount >= $targetCount) break; // 🔥 Target strict follow hoga

            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$alreadyToday) {
                $history = $this->getCustomerRemarkHistory($customer->id);
                $remark = "🔥 [Priority / Today FollowUp] Status: {$customer->status}";

                TelecallerAllocation::create([
                    'task_id'         => $task->id,
                    'phase_id'        => $task->phase_id,
                    'customer_id'     => $customer->id,
                    'assignee_type'   => $task->assignee_type,
                    'assignee_id'     => $task->assignee_id,
                    'call_status'     => 'Pending',
                    'assigned_status' => $customer->status,
                    'remark'          => $remark . $history,
                    'is_rollover'     => 1, // 🔥 FOLLOW-UP BHI ROLLOVER 1 HOTA HAI
                ]);
                $allocatedCount++;
            }
        }
        return $allocatedCount;
    }

    /**
     * CATEGORY 2: 3-DAY ROLLOVER (Unreachable/Busy leads)
     */
    public function allocateRolloverCustomers($task, $targetCount)
    {
        if ($targetCount <= 0) return 0;

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
            if ($allocatedCount >= $targetCount) break;

            $customer = $alloc->customer;
            if (!$customer || in_array($customer->status, $this->blacklistStatuses) || in_array($customer->status, $this->priorityStatuses)) {
                continue;
            }

            $badStatusCount = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereIn('call_status', $this->rolloverStatuses)
                ->count();

            if ($badStatusCount < 3) {
                $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)->whereDate('created_at', $today)->exists();

                if (!$alreadyToday) {
                    $history = $this->getCustomerRemarkHistory($customer->id);
                    TelecallerAllocation::create([
                        'task_id'         => $task->id,
                        'phase_id'        => $task->phase_id,
                        'customer_id'     => $customer->id,
                        'assignee_type'   => $task->assignee_type,
                        'assignee_id'     => $task->assignee_id,
                        'call_status'     => 'Pending',
                        'remark'          => '⚠️ [Rollover] ' . $customer->status . ' (Attempt ' . ($badStatusCount + 1) . ' of 3).' . $history,
                        'assigned_status' => $customer->status,
                        'is_rollover'     => 1, // 🔥 3-DAY ROLLOVER 1 JAYEGA
                    ]);
                    $allocatedCount++;
                }
            }
        }
        return $allocatedCount;
    }

   /**
     * CATEGORY 3: FRESH LEADS (SUPER FAST MASTER-TRANSACTION LOGIC)
     */
    public function allocateFreshCustomers($task, $targetCount)
    {
        if ($targetCount <= 0 || !$task->phase_id) return 0;
        $allocatedCount = 0;

        $assigneeRecord = $task->assignee_type::find($task->assignee_id);
        if (!$assigneeRecord) return 0;

        $memberId = $assigneeRecord->member_id ?? $task->assignee_id;
        $isEmployee = str_contains($task->assignee_type, 'Employee');

        // 🔥 THE MAGIC HAPPENS HERE: Purani heavy query hata di, sirf master column check kar rahe hain
        $baseQuery = InterestedCustomer::whereIn('status', ['Pending', 'pending', 'Pending status', 'General', 'general'])
            ->whereNotIn('status', $this->blacklistStatuses)
            ->where('is_assigned', 0); // 🔥 Naya Super Fast Filter

        // Company/Branch Mapping
        if ($task->company_id) $baseQuery->where('company_id', $task->company_id);
        if ($task->branch_id) $baseQuery->where('branch_id', $task->branch_id);

        // 🔥 STRICT ASSIGNMENT RULES (0001, null, ya khud employee ki member_id)
        if ($isEmployee) {
            $baseQuery->where(function ($q) {
                $q->where('is_member', 0)->orWhereNull('is_member');
            })->where(function ($q) use ($memberId) {
                $q->whereNull('assigned_telecaller')
                  ->orWhere('assigned_telecaller', 'ABDPL-A/0001')
                  ->orWhere('assigned_telecaller', $memberId);
            });
        } else {
            // Member/Admin Override (Agar khud login hai)
            $baseQuery->where('is_member', 1)
                      ->where('assigned_telecaller', $memberId)
                      ->whereNull('called_by');
        }

        $freshCustomers = collect();
        $providerPercent = isset($task->provider_percent) ? (int)$task->provider_percent : 50;

        // Provider Quota Check
        if (!empty($task->provider_id)) {
            $providerQuota = (int) ceil(($targetCount * $providerPercent) / 100);
            if ($providerQuota > 0) {
                $providerLeads = (clone $baseQuery)->where('provider_id', $task->provider_id)
                    ->orderBy('id', 'asc')
                    ->limit($providerQuota)
                    ->get();
                $freshCustomers = $freshCustomers->merge($providerLeads);
                $targetCount -= $providerLeads->count();
            }
        }

        // Fallback Data filling remaining target
        if ($targetCount > 0) {
            $fallbackLeads = (clone $baseQuery)
                ->when(!empty($task->provider_id), function ($query) use ($task) {
                    return $query->where(function ($q) use ($task) {
                        $q->where('provider_id', '!=', $task->provider_id)->orWhereNull('provider_id');
                    });
                })
                ->orderBy('id', 'asc')
                ->limit($targetCount)
                ->get();
            $freshCustomers = $freshCustomers->merge($fallbackLeads);
        }

        $newlyAssignedIds = [];
        foreach ($freshCustomers as $customer) {
            TelecallerAllocation::create([
                'task_id'         => $task->id,
                'phase_id'        => $task->phase_id,
                'customer_id'     => $customer->id,
                'assignee_type'   => $task->assignee_type,
                'assignee_id'     => $task->assignee_id,
                'call_status'     => 'Pending',
                'assigned_status' => 'Pending',
                'remark'          => '✨ [Fresh Lead] Picked from Master DB.',
                'is_rollover'     => 0, 
            ]);
            $newlyAssignedIds[] = $customer->id;
            $allocatedCount++;
        }

        // 🔥 GODOWN (MASTER TABLE) UPDATE KARNA 🔥
        if (!empty($newlyAssignedIds) && $memberId) {
            InterestedCustomer::whereIn('id', $newlyAssignedIds)
                ->update([
                    'is_assigned'         => 1, // Ek baar uth gayi toh flag on!
                    'assigned_telecaller' => $memberId,
                    'updated_at'          => now()
                ]);
        }

        return $allocatedCount;
    }

    public function allocateOverrideMemberLeads($task, $memberId, $status, $targetCount)
    {
        if ($targetCount <= 0 || !str_contains($task->assignee_type, 'Employee')) return 0;
        $allocatedCount = 0;

        // 🔥 Yahan bhi is_assigned check lagayenge taki jo already allocated hai wo dubara na aaye
        $query = InterestedCustomer::where('is_member', 1)
                                   ->where('assigned_telecaller', $memberId)
                                   ->where('is_assigned', 0); // 🔥 NAYA FILTER

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $leads = $query->limit($targetCount)->get();

        $newlyAssignedIds = [];
        foreach ($leads as $customer) {
            $history = $this->getCustomerRemarkHistory($customer->id);
            TelecallerAllocation::create([
                'task_id'         => $task->id,
                'phase_id'        => $task->phase_id,
                'customer_id'     => $customer->id,
                'assignee_type'   => $task->assignee_type,
                'assignee_id'     => $task->assignee_id,
                'call_status'     => 'Pending',
                'assigned_status' => $customer->status,
                'remark'          => "🚨 [Admin Override] Member ID {$memberId}. Status: {$customer->status} {$history}",
                'is_rollover'     => 0,
            ]);
            $newlyAssignedIds[] = $customer->id;
            $allocatedCount++;
        }

        // 🔥 GODOWN (MASTER TABLE) ME BHI UPDATE
        if ($allocatedCount > 0) {
            $assigneeRecord = $task->assignee_type::find($task->assignee_id);
            $employeeTelecallerId = $assigneeRecord->member_id ?? $task->assignee_id;
            
            InterestedCustomer::whereIn('id', $newlyAssignedIds)->update([
                'is_assigned'         => 1, // 🔥 FLAG ON KAR DIYA
                'assigned_telecaller' => $employeeTelecallerId,
                'is_member'           => 0,
                'updated_at'          => now()
            ]);
        }

        return $allocatedCount;
    }
}
