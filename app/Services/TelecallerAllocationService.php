<?php

namespace App\Services;

use App\Models\InterestedCustomer;
use App\Models\TelecallerAllocation;
use Illuminate\Support\Facades\DB;

class TelecallerAllocationService
{
    /**
     * 🔥 FIXED: Fresh customers dhoondhna aur unhe lock karna
     */
    public function allocateFreshCustomers($task, $targetCount)
    {
        if ($targetCount <= 0 || !$task->phase_id) {
            return 0;
        }

        $alreadyAssignedIds = TelecallerAllocation::where('phase_id', $task->phase_id)
            ->pluck('customer_id')
            ->toArray();

        $query = InterestedCustomer::whereNotIn('id', $alreadyAssignedIds);

        if ($task->company_id) {
            $query->where(function ($q) use ($task) {
                $q->where('company_id', $task->company_id)
                    ->orWhereNull('company_id');
            });
        }
        if ($task->branch_id) {
            $query->where(function ($q) use ($task) {
                $q->where('branch_id', $task->branch_id)
                    ->orWhereNull('branch_id');
            });
        }

        $freshCustomers = $query->limit($targetCount)->get();
        $allocatedCount = 0;

        foreach ($freshCustomers as $customer) {
            TelecallerAllocation::create([
                'task_id'       => $task->id,
                'phase_id'      => $task->phase_id,
                'customer_id'   => $customer->id,
                'assignee_type' => $task->assignee_type,
                'assignee_id'   => $task->assignee_id,
                'call_status'   => 'Pending',
            ]);
            $allocatedCount++;
        }

        return $allocatedCount;
    }

    /**
     * 🔥 THE 3-DAY RULE LOGIC & CRASH FIX 🔥
     */
    public function allocateRolloverCustomers($task)
    {
        // Ye 4 status wale customer hi rollover honge
        $rolloverStatuses = ['Not Reachable', 'Switch Off', 'Not Answering', 'Busy'];
        $allocatedCount = 0;

        // 1. Purane allocations se un customers ko dhoondho (is telecaller ke) jinka status in 4 me se tha
        $recentAllocations = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->whereIn('call_status', $rolloverStatuses)
            ->whereDate('created_at', '>=', now()->subDays(10)) // Taaki system fast rahe
            ->latest()
            ->get()
            ->unique('customer_id');

        foreach ($recentAllocations as $alloc) {
            $customer = $alloc->customer;

            // 2. Agar customer kisi dusre status (jaise Follow Up/Interested) me chala gaya hai, toh chhod do
            if (!$customer || !in_array($customer->status, $rolloverStatuses)) {
                continue;
            }

            // 3. Check karein is customer ko in dead statuses me ab tak KUL KITNI BAAR call mil chuki hai
            $badStatusCount = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereIn('call_status', $rolloverStatuses)
                ->count();

            // 4. 🔥 THE RULE: Agar 3 se kam hai, tabhi rollover hoga! (3 ho gaya toh chhut jayega)
            if ($badStatusCount < 3) {
                // Check if already assigned today
                $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyToday) {
                    TelecallerAllocation::create([
                        'task_id'       => $task->id,
                        'phase_id'      => $task->phase_id,
                        'customer_id'   => $customer->id,
                        'assignee_type' => $task->assignee_type,
                        'assignee_id'   => $task->assignee_id,
                        'call_status'   => 'Pending',
                        // SMART REMARK: Ye telecaller ko batayega ki ye konsa attempt hai
                        'remark'        => '⚠️ [Rollover] ' . $customer->status . ' (Attempt ' . ($badStatusCount + 1) . ' of 3).',
                    ]);
                    $allocatedCount++;
                }
            }
        }

        return $allocatedCount;
    }

    /**
     * 🔥 NAYA: Kal ke bache hue (Pending) customers ko nikalna
     */
    public function allocatePendingLeftovers($task)
    {
        // Un customers ko dhoondho jo is telecaller ke paas the aur kal Pending reh gaye
        $recentAllocations = TelecallerAllocation::where('assignee_id', $task->assignee_id)
            ->where('assignee_type', $task->assignee_type)
            ->where('call_status', 'Pending')
            ->where('task_id', '!=', $task->id) // Aaj ka task chhod kar
            ->whereDate('created_at', '>=', now()->subDays(5)) // Pichle 5 din ka backlog
            ->latest()
            ->get()
            ->unique('customer_id');

        $allocatedCount = 0;

        foreach ($recentAllocations as $alloc) {
            $customer = $alloc->customer;
            
            // Master table me bhi status 'Pending' hona chahiye (yaani usne sach me call nahi kiya)
            if (!$customer || $customer->status !== 'Pending') {
                continue; 
            }

            // Duplicate se bachne ke liye aaj ki check
            $alreadyToday = TelecallerAllocation::where('customer_id', $customer->id)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (!$alreadyToday) {
                TelecallerAllocation::create([
                    'task_id'       => $task->id,
                    'phase_id'      => $task->phase_id,
                    'customer_id'   => $customer->id,
                    'assignee_type' => $task->assignee_type,
                    'assignee_id'   => $task->assignee_id,
                    'call_status'   => 'Pending', 
                    'remark'        => '⚠️ [Rollover] Yesterday Leftover (Not Called).', // Alag remark
                ]);
                $allocatedCount++;
            }
        }

        return $allocatedCount;
    }
}
