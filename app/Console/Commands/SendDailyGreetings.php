<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GreetingTemplate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GreetingNotification;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;

class SendDailyGreetings extends Command
{
    protected $signature = 'greetings:send-daily';
    protected $description = 'Send daily birthday, anniversary, and work anniversary greetings.';

    public function handle()
    {
        $today = now()->format('m-d');
        $currentYear = now()->format('Y');
        
        // Templates load karein
        $templates = GreetingTemplate::pluck('template_text', 'event_type')->toArray();
        if (empty($templates)) {
            $this->warn('No greeting templates found. Skipping...');
            return;
        }

      $modelsConfig = [
            ['class' => \App\Models\Employee::class, 'type' => 'Employee', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => 'doj', 'status_col' => 'emp_status'],
            ['class' => \App\Models\Agent::class, 'type' => 'Agent', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => 'joining_date', 'status_col' => 'agent_status'],
            ['class' => \App\Models\Director::class, 'type' => 'Director', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'status'],
            ['class' => \App\Models\Landowner::class, 'type' => 'Landowner', 'birthday' => 'lo_dob', 'anniversary' => null, 'work' => null, 'status_col' => 'status'],
            ['class' => \App\Models\Member::class, 'type' => 'Member', 'birthday' => 'dob', 'anniversary' => 'date_of_anniversary', 'work' => 'doj', 'status_col' => 'status'],
            ['class' => \App\Models\Vendor::class, 'type' => 'Vendor', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'vendor_status'],
            ['class' => \App\Models\SuperAdmin::class, 'type' => 'SuperAdmin', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'status'],
            // 🎯 NAYA: Customer entry
            ['class' => \App\Models\Customer::class, 'type' => 'Customer', 'birthday' => 'dob', 'anniversary' => 'date_of_anniversary', 'work' => null, 'status_col' => 'status'],
        ];

        
        foreach ($modelsConfig as $config) {
            $modelClass = $config['class'];
            $statusCol = $config['status_col'];
            $queryBase = $modelClass::where($statusCol, 'active');

            if ($config['birthday']) {
                $birthdays = (clone $queryBase)->whereRaw("DATE_FORMAT({$config['birthday']}, '%m-%d') = ?", [$today])->get();
                $this->processEvents($birthdays, 'birthday', $templates['birthday'] ?? null, $config['type'], 'fa-birthday-cake', 'text-warning');
            }

            if ($config['anniversary']) {
                $anniversaries = (clone $queryBase)->whereRaw("DATE_FORMAT({$config['anniversary']}, '%m-%d') = ?", [$today])->get();
                $this->processEvents($anniversaries, 'anniversary', $templates['anniversary'] ?? null, $config['type'], 'fa-heart', 'text-danger');
            }

           if ($config['work']) {
                $workAnniversaries = (clone $queryBase)->whereRaw("DATE_FORMAT({$config['work']}, '%m-%d') = ?", [$today])
                                               ->whereRaw("YEAR({$config['work']}) < ?", [$currentYear]) // Yahan $currentYear aayega
                                               ->get();
                $this->processEvents($workAnniversaries, 'work_anniversary', $templates['work_anniversary'] ?? null, $config['type'], 'fa-briefcase', 'text-success');
            }
        }
    }


   private function processEvents($users, $eventType, $template, $userType, $icon, $color)
    {
        if (!$template || $users->isEmpty()) return;

        foreach ($users as $user) {
            $name = $user->name ?? $user->full_name ?? $user->member_name ?? $user->employee_name ?? $user->customer_name ?? 'Valued Associate';
            $companyName = $user->company->name ?? 'Janki Villa';
            
            $years = '';
            if ($eventType === 'work_anniversary') {
                $dojColumn = isset($user->doj) ? 'doj' : 'joining_date';
                $years = Carbon::parse($user->$dojColumn)->age; 
            }

            $message = str_replace(
                ['[Name]', '[Company]', '[Years]'], 
                [$name, $companyName, $years], 
                $template
            );

            $title = "Happy " . ucwords(str_replace('_', ' ', $eventType)) . "!";

            // 🎯 LOGIC: User ke type ke hisaab se sahi portal ka URL generate karna
            $portal = 'admin'; // Default admin
            if ($userType === 'Employee') {
                $portal = 'employee';
            } elseif ($userType === 'Member') {
                $portal = 'member';
            } elseif ($userType === 'Customer') {
                $portal = 'customer';
            }
            
            // Final Exact URL banaya (eg: http://domain.com/employee/my-greetings)
            $targetUrl = url('/' . $portal . '/my-greetings');

            // A. Individual ko notification bhejna (Sahi URL ke sath)
            $user->notify(new GreetingNotification($title, $message, $icon, $color, $targetUrl));

            // B. Management ko alert bhejna
            $companyId = $user->company_id ?? null;
            $managementTargets = NotificationHelper::getTargets($companyId, null, null); 

            $managementMessage = "Today is {$name}'s ({$userType}) " . ucwords(str_replace('_', ' ', $eventType)) . "!";
            $managementTitle = "Event Reminder";
            
            // Management click karegi to unhe upcoming-events page par bhejenge
            $managementUrl = url('/admin/upcoming-events');

            if ($managementTargets && $managementTargets->isNotEmpty()) {
                Notification::send($managementTargets, new GreetingNotification($managementTitle, $managementMessage, 'fa-calendar-check', 'text-info', $managementUrl));
            }
        }
    }
}