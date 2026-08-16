<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\TelecallerAllocation;
use App\Services\TelecallerAllocationService;
use Illuminate\Support\Facades\DB;

class FixAndReallocateToday extends Command
{
    protected $signature = 'telecaller:fix-allocations';
    protected $description = 'Deletes today incorrect allocations and re-assigns them based on the updated strict rules';

    public function handle(TelecallerAllocationService $allocationService)
    {
        $this->info('Starting cleanup of today incorrect allocations...');
        $today = now()->toDateString();

        DB::beginTransaction();
        try {
            // 1. Aaj ki saari allocations delete kar do
            $deletedCount = TelecallerAllocation::whereDate('created_at', $today)->delete();
            $this->info("Deleted {$deletedCount} incorrect allocations of today.");

            // 2. Aaj ke saare active tasks nikalo jinka target bacha hai
            $todayTasks = Task::whereDate('created_at', $today)
                ->where('target_count', '>', 0)
                ->get();

            if ($todayTasks->isEmpty()) {
                $this->warn('No tasks found for today to re-allocate.');
                DB::commit();
                return;
            }

            $reAssignedTotal = 0;

            foreach ($todayTasks as $task) {
                $this->info("Re-allocating for Task ID: {$task->id} ({$task->title})");
                
                $remainingTarget = $task->target_count;

                // Priority, Leftover, Fresh saare steps dobara run honge naye rules ke sath
                $priorityCount = $allocationService->allocatePriorityCustomers($task);
                $remainingTarget -= $priorityCount;

                $leftoverCount = 0;
                if ($remainingTarget > 0) {
                    $leftoverCount = $allocationService->allocatePendingLeftovers($task);
                    $remainingTarget -= $leftoverCount;
                }

                $freshCount = 0;
                if ($remainingTarget > 0) {
                    $freshCount = $allocationService->allocateFreshCustomers($task, $remainingTarget);
                }

                $rolloverCount = $allocationService->allocateRolloverCustomers($task);

                $totalAllocated = $priorityCount + $leftoverCount + $freshCount + $rolloverCount;
                
                // Task ka achieved/target count update karo
                $task->update(['target_count' => $totalAllocated]);
                $reAssignedTotal += $totalAllocated;

                $this->info("-> Assigned: {$totalAllocated} calls ({$priorityCount} Priority, {$freshCount} Fresh, {$leftoverCount} Leftover, {$rolloverCount} Rollover)");
            }

            DB::commit();
            $this->info("Success! Cleaned up and successfully re-assigned total {$reAssignedTotal} clean leads.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during reallocation: " . $e->getMessage());
        }
    }
}