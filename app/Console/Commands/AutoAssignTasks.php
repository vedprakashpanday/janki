<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutoTaskSetting;
use App\Models\Task;
use App\Models\TaskProgressLog;
use App\Models\Holiday;
use App\Models\LeaveApplication;
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

        $todayCarbon = now();
        $todayDate = $todayCarbon->toDateString();

        if ($todayCarbon->isTuesday()) {
            $this->info("Today is Tuesday (Weekly Off). Skipping all auto task assignments.");
            return;
        }

        $isHoliday = Holiday::where('status', 'active')
            ->where(function ($query) use ($todayDate) {
                $query->where(function ($q) use ($todayDate) {
                    $q->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $todayDate)
                        ->whereDate('end_date', '>=', $todayDate);
                })->orWhere(function ($q) use ($todayDate) {
                    $q->whereNull('end_date')
                        ->whereDate('start_date', $todayDate);
                });
            })->exists();

        if ($isHoliday) {
            $this->info("Today is a Holiday. Skipping all auto task assignments.");
            return;
        }

        $currentTime = $todayCarbon->format('H:i');

        $settings = AutoTaskSetting::where('is_active', true)
            ->where(DB::raw('LEFT(run_time, 5)'), $currentTime)
            ->get();

        if ($settings->isEmpty()) {
            $this->info("No tasks scheduled for {$currentTime}.");
            return;
        }

        $tasksCreated = 0;

        foreach ($settings as $setting) {

            $userTypeForLeave = str_contains($setting->assignee_type, 'Member') ? 'member' : 'employee';

            $isOnLeave = LeaveApplication::where('user_id', $setting->assignee_id)
                ->where('user_type', $userTypeForLeave)
                ->where('application_type', 'Leave')
                ->where('status', 'approved')
                ->whereDate('approved_start_datetime', '<=', $todayDate)
                ->whereDate('approved_end_datetime', '>=', $todayDate)
                ->exists();

            if ($isOnLeave) {
                $this->info("Assignee ID {$setting->assignee_id} is on Approved Leave today. Skipping task assignment.");
                continue;
            }

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
                    'provider_id'        => $setting->provider_id ?? null,
                    'provider_percent'   => $setting->provider_percent ?? 50, 
                ]);

                if ($task->phase_id && $task->target_count > 0) {
                    
                    $remainingTarget = $task->target_count; // Daily Target (e.g., 200)

                    // 1. Leftovers (Kal ki uncalled leads) -> Ye target se minus nahi hongi, extra add hongi
                    $leftoverCount = $allocationService->allocatePendingLeftovers($task);

                    // 2. Follow-up Priority -> Target quota ko consume karega
                    $priorityCount = $allocationService->allocatePriorityCustomers($task, $remainingTarget);
                    $remainingTarget -= $priorityCount;

                    // 3. Rollover (3-Days unreachable) -> Target quota consume karega
                    $rolloverCount = 0;
                    if ($remainingTarget > 0) {
                        $rolloverCount = $allocationService->allocateRolloverCustomers($task, $remainingTarget);
                        $remainingTarget -= $rolloverCount;
                    }

                    // 4. Fresh Leads (Bache hue target ke liye)
                    $freshCount = 0;
                    if ($remainingTarget > 0) {
                        $freshCount = $allocationService->allocateFreshCustomers($task, $remainingTarget);
                        $remainingTarget -= $freshCount;
                    }

                    // Total math: e.g., 50 Leftover + 200 (Priority + Rollover + Fresh) = 250
                    $totalAllocated = $leftoverCount + $priorityCount + $rolloverCount + $freshCount;
                    $task->update(['target_count' => $totalAllocated]);

                    $breakdownMsg = "Assigned {$totalAllocated} calls ({$leftoverCount} Leftover Backlog, {$priorityCount} Priority, {$rolloverCount} Rollover, {$freshCount} Fresh).";
                } else {
                    $totalAllocated = $task->target_count ?? 0;
                    $breakdownMsg = "Aapko {$totalAllocated} calls ka general task assign kiya gaya hai. Kripya apna portal check karein.";
                }

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
                        "Daily Task Assigned",
                        $breakdownMsg,
                        "/{$portal}/my-calling-portal?filter=today",
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