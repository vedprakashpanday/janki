<?php

namespace App\Services;

use App\Models\InterestedCustomer;
use App\Models\TelecallerAllocation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelecallerAllocationService
{
    // 🔥 THE 3 MASTER CATEGORIES 🔥
    // 1. Blacklist: Inhe dobara KABHI assign nahi karna hai[cite: 5]
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

    // 2. Rollover: 3 din tak retry karne wale status[cite: 5]
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

    // 3. Priority/Scheduled: Sabse pehle assign honge[cite: 5]
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
     * 🔥 NAYA HELPER: CUMULATIVE REMARKS HISTORY LAANE KE LIYE[cite: 5]
     * ========================================================
     */
    private function getCustomerRemarkHistory($customerId)
    {
        // Allocation table se picchli successful calls nikalenge[cite: 5]
        $historyRecords = TelecallerAllocation::where('customer_id', $customerId)
            ->whereNotNull('called_at')
            ->whereNotNull('remark')
            ->orderBy('called_at', 'desc')
            ->take(3) // Last 3 interactions ki limit taaki box bohot bada na ho jaye[cite: 5]
            ->get();

        if ($historyRecords->isEmpty()) {
            // Agar allocation me nahi hai, toh master table se last remark utha lo[cite: 5]
            $cust = InterestedCustomer::find($customerId);
            return ($cust && $cust->remark) ? "\n--- History ---\n[Prev]: " . $cust->remark : "";
        }

        $historyText = "\n\n--- History ---";
        foreach ($historyRecords as $rec) {
            $date = Carbon::parse($rec->called_at)->format('d-M-y');
            // Purane system tags (Priority/Rollover) filter karke hata denge[cite: 5]
            $cleanRemark = preg_replace('/^(🔥|⚠️|⏰|✨)\s*\[.*?\]\s*/', '', $rec->remark);
            $historyText .= "\n[{$date} | {$rec->call_status}]: {$cleanRemark}";
        }
        return $historyText;
    }

    /**
     * ========================================================
     * CATEGORY 1: PRIORITY & SCHEDULED (Adjusted in 250 Target)[cite: 5]
     * ========================================================
     */
  public function allocatePriorityCustomers($task)
    {
        $today = now()->toDateString();

        // 🔥 YAHAN QUERY UPDATE KI GAYI HAI 🔥
        $priorityCustomers = InterestedCustomer::where('assigned_telecaller', $task->assignee_id)
            ->where(function ($q) use ($today) {
                
                // Rule 1: Priority Status HO, LEKIN Follow-up date aaj/past ki ho, ya phir NULL ho (future na ho)
                $q->where(function($subQ1) use ($today) {
                    $subQ1->whereIn('status', $this->priorityStatuses)
                          ->where(function($dateQ) use ($today) {
                              $dateQ->whereNull('followup_date')
                                    ->orWhereDate('followup_date', '<=', $today);
                          });
                })
                // Rule 2: YA PHIR kisi aur status ki lead ho jiska Follow-up date strictly aaj ya past ka ho
                ->orWhere(function ($subQ2) use ($today) {
                    $subQ2->whereNotNull('followup_date')
                          ->whereDate('followup_date', '<=', $today);
                });

            })->get();

        $allocatedCount = 0;

        foreach ($priorityCustomers as $customer) {
            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$alreadyToday) {
                // 🔥 NAYA: History attach karo[cite: 5]
                $history = $this->getCustomerRemarkHistory($customer->id);

                $remark = "🔥 [Priority] Status: {$customer->status}";
                if ($customer->status === 'On Hold' && $customer->updated_at) {
                    $remark = "⚠️ [Scheduled] Last status was 'On Hold' on " . $customer->updated_at->format('d-M-Y');
                } elseif ($customer->followup_date && $customer->followup_date <= $today) {
                    $remark = "⏰ [Follow-Up] Scheduled for: " . Carbon::parse($customer->followup_date)->format('d-M-Y');
                }

                $remark .= $history; // Attach history here[cite: 5]

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
        // 1. TODAY'S & MISSED FOLLOW UPS (Leave/Holiday Logic)
        // ==========================================
        // Yahan '<=' lagaya hai taaki kal ya parso ke chhute hue follow-ups bhi aa jayein[cite: 5]
        $myFollowUpCustomerIds = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->whereNotNull('followup_date')
            ->whereDate('followup_date', '<=', $today)
            ->pluck('customer_id')
            ->toArray();

        if (!empty($myFollowUpCustomerIds)) {
            $followUpQuery = InterestedCustomer::whereIn('id', $myFollowUpCustomerIds)
                // 🔥 FIX: Lost/Blacklisted leads ko auto-assign hone se rokna
                ->whereNotIn('status', $this->blacklistStatuses);

            if ($task->company_id) $followUpQuery->where('company_id', $task->company_id);
            if ($task->branch_id) $followUpQuery->where('branch_id', $task->branch_id);

            // Jo aaj already assign ho gaye unko chhod do[cite: 5]
            $alreadyAssignedTodayIds = TelecallerAllocation::where('assignee_id', $task->assignee_id)
                ->whereDate('created_at', $today)
                ->pluck('customer_id')->toArray();

            $followUpQuery->whereNotIn('id', $alreadyAssignedTodayIds);

            $priorityCustomers = $followUpQuery->limit($targetCount)->get();

            foreach ($priorityCustomers as $customer) {
                $history = $this->getCustomerRemarkHistory($customer->id);

                // Missed Follow-up Identification Logic[cite: 5]
                $followUpDate = Carbon::parse($customer->followup_date)->toDateString();
                if ($followUpDate < $today) {
                    $tag = "🚨 [Missed FollowUp - {$followUpDate}]"; // Agar chhut gaya tha[cite: 5]
                } else {
                    $tag = "⚠️ [Today FollowUp]"; // Agar aaj ka hi hai[cite: 5]
                }

                TelecallerAllocation::create([
                    'task_id'         => $task->id,
                    'phase_id'        => $task->phase_id,
                    'customer_id'     => $customer->id,
                    'assignee_type'   => $task->assignee_type,
                    'assignee_id'     => $task->assignee_id,
                    'call_status'     => 'Pending',
                    'assigned_status' => $customer->status,
                    'remark'          => "{$tag} Last Status: {$customer->status} {$history}",
                ]);
                $allocatedCount++;
            }
        }

        // ==========================================
        // 2. FRESH PENDING LEADS (Custom % Rule)
        // ==========================================
        $remainingTarget = $targetCount - $allocatedCount;

        if ($remainingTarget > 0) {
            $alreadyAssignedPhaseIds = TelecallerAllocation::where('phase_id', $task->phase_id)
                ->pluck('customer_id')->toArray();

            // Base Query
           $baseQuery = InterestedCustomer::whereIn('status', ['Pending', 'pending', 'Pending status', 'General', 'general'])
                ->whereNotIn('id', $alreadyAssignedPhaseIds)
                // 🔥 THE SECURITY LOCK: Sirf normal data uthega, Member ka nahi
                ->where(function($q) {
                    $q->where('is_member', 0)->orWhereNull('is_member');
                });

            if ($task->company_id) $baseQuery->where('company_id', $task->company_id);
            if ($task->branch_id) $baseQuery->where('branch_id', $task->branch_id);

            $freshCustomers = collect();

            // 🔥 FIX: Dynamic Percentage Rule Calculation
            // Task table se provider_percent uthana, agar nahi hai toh default 50% manna
            $providerPercent = isset($task->provider_percent) ? (int)$task->provider_percent : 50;
            $providerQuota = (int) ceil(($remainingTarget * $providerPercent) / 100);

            // STEP 2A: The Custom % from Selected Provider
            if (!empty($task->provider_id) && $providerQuota > 0) {
                $providerQuery = clone $baseQuery;
                $providerLeads = $providerQuery->where('provider_id', $task->provider_id)
                    ->orderBy('id', 'asc')
                    ->limit($providerQuota) // Strictly uthaega
                    ->get();

                $freshCustomers = $freshCustomers->merge($providerLeads);
                $remainingTarget -= $providerLeads->count();
            }

            // STEP 2B: Fallback (Remaining % + Agar Provider Leads Kam Padi)
            if ($remainingTarget > 0) {
                $fallbackQuery = clone $baseQuery;

                $fallbackQuery->when(!empty($task->provider_id), function ($query) use ($task) {
                    return $query->where(function ($q) use ($task) {
                        $q->where('provider_id', '!=', $task->provider_id)
                            ->orWhereNull('provider_id');
                    });
                });

                $fallbackLeads = $fallbackQuery->orderBy('id', 'asc')
                    ->limit($remainingTarget)
                    ->get();

                $freshCustomers = $freshCustomers->merge($fallbackLeads);
            }

            // Aakhir me sabko database me assign kar do
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

        // Master Table me Telecaller ko permanently lock karna
        if ($allocatedCount > 0) {
            // Telecaller ki actual ID ya Member_ID nikalna[cite: 5]
            $assigneeRecord = $task->assignee_type::find($task->assignee_id);
            $telecallerId = $assigneeRecord->member_id ?? $task->assignee_id;

            // Jo IDs aaj assign hui hain, unhe TelecallerAllocation se nikal kar update karna[cite: 5]
            $newlyAssignedCustomerIds = \App\Models\TelecallerAllocation::where('task_id', $task->id)
                ->where('call_status', 'Pending')
                ->pluck('customer_id')
                ->toArray();

            if (!empty($newlyAssignedCustomerIds)) {
                \App\Models\InterestedCustomer::whereIn('id', $newlyAssignedCustomerIds)
                    ->update([
                        'assigned_telecaller' => $telecallerId,
                        'updated_at' => now()
                    ]);
            }
        }

        return $allocatedCount;
    }
    /**
     * ========================================================
     * CATEGORY 3: THE 3-DAY ROLLOVER (Target ke upar EXTRA)[cite: 5]
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
                    // 🔥 NAYA: History attach karo[cite: 5]
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
     * YESTERDAY'S PENDING LEFTOVER (Jo kal chhut gaye the)[cite: 5]
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
                // 🔥 NAYA: History attach karo[cite: 5]
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

    /**
     * ========================================================
     * 🔥 ADMIN OVERRIDE: MEMBER KA DATA EMPLOYEE KO DENA 🔥
     * ========================================================
     */
    public function allocateOverrideMemberLeads($task, $memberId, $status, $targetCount)
    {
        if ($targetCount <= 0) return 0;
        $allocatedCount = 0;

        $query = InterestedCustomer::where('is_member', 1)->where('member_id', $memberId);
        
        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        // Taki same task me duplicate leads na jayein
        $alreadyAssignedPhaseIds = TelecallerAllocation::where('phase_id', $task->phase_id)
            ->pluck('customer_id')->toArray();
            
        $leads = $query->whereNotIn('id', $alreadyAssignedPhaseIds)
            ->limit($targetCount)
            ->get();

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
                'remark'          => "🚨 [Admin Override] Member (ID: {$memberId}) ki lead assign ki gayi hai. Old Status: {$customer->status} {$history}",
            ]);
            $allocatedCount++;
        }

        // Master Table me naye employee ko lock kar do
        if ($allocatedCount > 0) {
            $assigneeRecord = $task->assignee_type::find($task->assignee_id);
            $telecallerId = $assigneeRecord->member_id ?? $task->assignee_id;
            $newlyAssignedIds = $leads->pluck('id')->toArray();

            InterestedCustomer::whereIn('id', $newlyAssignedIds)->update([
                'assigned_telecaller' => $telecallerId,
                'is_member' => 0, // 🔥 Isko wapas 0 kar do taaki ab ye normal employee ki lead ban jaye
                'updated_at' => now()
            ]);
        }

        return $allocatedCount;
    }




}
