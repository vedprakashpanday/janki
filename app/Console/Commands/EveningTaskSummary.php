<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\TelecallerAllocation;
use App\Models\TaskProgressLog;
use App\Models\Employee;
use App\Notifications\SystemAlertNotification;
use App\Helpers\NotificationHelper; 
use Illuminate\Support\Facades\Notification; 

class EveningTaskSummary extends Command
{
    protected $signature = 'task:evening-summary';
    protected $description = 'Send daily evening chat summary of Interested & Site Visit leads and notify ADMINS only.';

    public function handle()
    {
        $this->info('Starting Evening Task Summary Generation...');
        $today = now()->toDateString();

        $tasks = Task::whereDate('created_at', $today)->get();

        foreach ($tasks as $task) {
            
            // 🔥 FIX: Sirf aaj "called_at" hone wali aur required status wali leads
            $hotAllocations = TelecallerAllocation::with('customer')
                ->where('task_id', $task->id)
                ->whereDate('called_at', $today) // AAJ KA FILTER
                ->where(function($q) {
                    $q->whereIn('call_status', [
                        'Interested', 'Interested Call', 'Highly Interested',
                        'Site visit Scheduled', 'Site visit Scheduled Call',
                        'Site Visit Done', 'Site Visit Done Call'
                    ]);
                })->get();

            if ($hotAllocations->count() > 0) {
                
                $message = "🌟 **Aaj Ki Hot Leads (Interested & Site Visit) Report** 🌟<br><br>";
                $count = 1;
                
                foreach ($hotAllocations as $alloc) {
                    $cust = $alloc->customer;
                    $name = $cust->cust_name ?? 'N/A';
                    $mobile = $cust->mobile ?? 'N/A';
                    $refer = $cust->refer_by ?? 'Direct / None';
                    $status = $alloc->call_status ?? 'Interested';

                    $message .= "{$count}. **{$name}** | 📞 {$mobile} | 🤝 Refer: {$refer} | Status: **{$status}**<br>";
                    $count++;
                }

                $message .= "<br>_Ye report system dwara automatically extract ki gayi hai._";

                TaskProgressLog::create([
                    'task_id'           => $task->id,
                    'actor_type'        => $task->assignee_type,
                    'actor_id'          => $task->assignee_id,
                    'log_type'          => 'progress_update',
                    'message_or_remark' => $message,
                    'entries_completed' => 0
                ]);

                $userRecord = null;
                if (str_contains($task->assignee_type, 'Employee')) {
                    $userRecord = Employee::where('id', $task->assignee_id)->orWhere('member_id', $task->assignee_id)->first();
                } else {
                    $userRecord = $task->assignee_type::find($task->assignee_id);
                }

                $companyId = $userRecord ? $userRecord->company_id : null;
                $branchId = $userRecord ? $userRecord->branch_id : null;
                $empName = $userRecord ? ($userRecord->full_name ?? $userRecord->member_name) : 'Telecaller';

                $targets = NotificationHelper::getTargets($companyId, $branchId);

                if ($targets->count() > 0) {
                    Notification::send($targets, new SystemAlertNotification(
                        "Hot Leads Summary 🌟",
                        "Employee {$empName} ki aaj ki Hot Leads report chat me update ho gayi hai.",
                        "/admin/tasks", 
                        "fa-star",
                        "text-success"
                    ));
                }

                $this->info("Chat Summary & Admin Notification posted for Task ID: {$task->id}");
            }
        }

        $this->info('Evening Task Summaries generated successfully.');
    }
}