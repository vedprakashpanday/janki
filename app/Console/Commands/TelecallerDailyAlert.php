<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelecallerAllocation;
use App\Models\Employee;
use App\Notifications\SystemAlertNotification;

class TelecallerDailyAlert extends Command
{
    protected $signature = 'task:daily-alert';
    protected $description = 'Send daily morning alerts ONLY to the assigned Telecaller based on allocations.';

    public function handle()
    {
        $this->info('Starting Daily Alerts for Telecallers...');

        $today = now()->toDateString();
        $todayMonthDay = now()->format('m-d');

        // 🔥 SIRF ALLOCATION TABLE SE MATCH KARENGE 🔥
        // Un sabhi employees ki list nikalenge jinka aaj koi bhi reminder set hai
        $targetAllocations = TelecallerAllocation::select('assignee_id', 'assignee_type')
            ->whereDate('followup_date', $today)
            ->orWhereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$todayMonthDay])
            ->orWhereRaw("DATE_FORMAT(anniversary_date, '%m-%d') = ?", [$todayMonthDay])
            ->distinct()
            ->get();

        foreach ($targetAllocations as $alloc) {
            $empId = $alloc->assignee_id;
            $empType = $alloc->assignee_type;

            // 1. Follow-ups (Direct Allocation table se count)
            $followUpCount = TelecallerAllocation::where('assignee_id', $empId)
                ->where('assignee_type', $empType)
                ->whereDate('followup_date', $today)
                ->count();

            // 2. Birthdays (Direct Allocation table se count)
            $dobCount = TelecallerAllocation::where('assignee_id', $empId)
                ->where('assignee_type', $empType)
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$todayMonthDay])
                ->count();

            // 3. Anniversaries (Direct Allocation table se count)
            $annivCount = TelecallerAllocation::where('assignee_id', $empId)
                ->where('assignee_type', $empType)
                ->whereRaw("DATE_FORMAT(anniversary_date, '%m-%d') = ?", [$todayMonthDay])
                ->count();

            $totalAlerts = $followUpCount + $dobCount + $annivCount;

            if ($totalAlerts > 0) {
                // 🔥 Yahan seedha find($id) kaam karega kyunki assignee_id real 'id' hai
                $userRecord = null;
                if (str_contains($empType, 'Employee')) {
                    $userRecord = Employee::find($empId);
                } else {
                    $userRecord = class_exists($empType) ? $empType::find($empId) : null;
                }

                if ($userRecord) {
                    $msg = "Aaj aapke list mein ";
                    $parts = [];
                    if ($followUpCount > 0) $parts[] = "{$followUpCount} Follow-ups";
                    if ($dobCount > 0) $parts[] = "{$dobCount} Birthdays";
                    if ($annivCount > 0) $parts[] = "{$annivCount} Anniversaries";

                    $msg .= implode(', ', $parts) . " scheduled hain. Kripya apna portal check karein.";

                    $portal = str_contains($empType, 'Employee') ? 'employee' : 'admin';

                    // Sirf aur sirf us particular Employee ko Notification bhejna
                    $userRecord->notify(new SystemAlertNotification(
                        "Today's Scheduled Calls 📅",
                        $msg,
                        "/{$portal}/my-calling-portal?filter=today",
                        "fa-calendar-day",
                        "text-warning"
                    ));

                    $this->info("Alert sent to {$empType} ID: {$empId} (Total: {$totalAlerts})");
                }
            }
        }

        $this->info('Daily Alerts Completed Successfully!');
    }
}
