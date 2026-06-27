<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutoTaskSetting;
use App\Models\Task;
use App\Models\TaskProgressLog;
use App\Services\TelecallerAllocationService;
use Illuminate\Support\Facades\DB;
use App\Notifications\SystemAlertNotification;

class AutoAssignTasks extends Command
{
    protected $signature = 'task:auto-assign';
    protected $description = 'Run daily auto-task settings and allocate fresh telecalling data';

    public function handle(\App\Services\TelecallerAllocationService $allocationService)
    {
        $this->info('Starting Auto Task Assignment...');

        $currentTime = now()->format('H:i');

        $settings = \App\Models\AutoTaskSetting::where('is_active', true)
            ->where(\Illuminate\Support\Facades\DB::raw('LEFT(run_time, 5)'), $currentTime)
            ->get();

        if ($settings->isEmpty()) {
            $this->info("No tasks scheduled for {$currentTime}.");
            return;
        }

        $tasksCreated = 0;

        foreach ($settings as $setting) {
            DB::beginTransaction();
            try {
                $assigneeRecord = $setting->assignee_type::find($setting->assignee_id);

                $empCompanyId = $assigneeRecord->company_id ?? $setting->company_id;
                $empBranchId  = $assigneeRecord->branch_id ?? $setting->branch_id;
                $empDeptId    = $assigneeRecord->department_id ?? null;

                $task = Task::create([
                    'company_id'         => $empCompanyId,
                    'branch_id'          => $empBranchId,
                    'department_id'      => $empDeptId,
                    'assigner_type'      => 'App\Models\Employee',
                    'assigner_id'        => 0,
                    'assignee_type'      => $setting->assignee_type,
                    'assignee_id'        => $setting->assignee_id,
                    'title'              => $setting->title_template . ' (' . now()->format('d M') . ')',
                    'description'        => $setting->description_template,
                    'tracking_module_id' => $setting->tracking_module_id,
                    'phase_id'           => $setting->phase_id,
                    'target_count'       => $setting->daily_target_count,
                    'priority'           => $setting->priority,
                    'due_datetime'       => now()->endOfDay(),
                    'status'             => 'Pending',
                ]);

               // ----------------------------------------------------
                // 🔥 NAYA LOGIC: FIXED TARGET + BREAKDOWN 🔥
                // ----------------------------------------------------
                $deadRolloverCount = 0;
                $pendingLeftoverCount = 0;
                $freshAllocatedCount = 0;

                if ($task->phase_id && $task->target_count > 0) {
                    
                    // 1. Purane 'Pending' (Jo kal call hi nahi hue)
                    $pendingLeftoverCount = $allocationService->allocatePendingLeftovers($task);

                    // 2. 'Not Reachable', 'Switch Off' etc. (3-Day rule)
                    $deadRolloverCount = $allocationService->allocateRolloverCustomers($task);

                    // 3. Fixed Fresh Target (Jaise 300 naye)
                    $freshAllocatedCount = $allocationService->allocateFreshCustomers($task, $task->target_count);
                }

                // Total actual allocation calculation (e.g., 300 + 160 + 60 = 520)
                $totalAllocated = $freshAllocatedCount + $pendingLeftoverCount + $deadRolloverCount;

                // 🔥 UI CARD FIX: Task table ko naye total se update kar do taaki card par 300 ki jagah 520 dikhe 🔥
                $task->update(['target_count' => $totalAllocated]);

                // Clear Breakdown Message
                $breakdownMsg = "Assigned {$totalAllocated} calls ({$freshAllocatedCount} Fresh, {$pendingLeftoverCount} Yesterday Left, {$deadRolloverCount} Dead Status).";

                TaskProgressLog::create([
                    'task_id'           => $task->id,
                    'actor_type'        => 'App\Models\Employee', 
                    'actor_id'          => 0, 
                    'log_type'          => 'progress_update',
                    'message_or_remark' => "System Note: " . $breakdownMsg,
                    'entries_completed' => 0
                ]);

                if ($assigneeRecord) {
                    $portal = str_contains($setting->assignee_type, 'Employee') ? 'employee' : 'admin';
                    $assigneeRecord->notify(new SystemAlertNotification(
                        "Daily Target Assigned",
                        $breakdownMsg, // User ki bell icon me ye poora breakdown aayega
                        "/{$portal}/tasks",
                        "fa-bullseye",
                        "text-primary"
                    ));
                }

                DB::commit();
                $tasksCreated++;
                
                $this->info("Assigned to ID {$setting->assignee_id}: " . $breakdownMsg);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed for Setting ID {$setting->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed! Total {$tasksCreated} tasks auto-assigned.");
    }
}
