<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelecallerAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendDailyCallingReport extends Command
{
    // Command ka naam jo kernel me call hoga
    protected $signature = 'report:daily-calling';

    protected $description = 'Send daily telecalling performance and late calling report at 18:16';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        
        // 1. Aapke bataye gaye specific "Connected" statuses
        $connectedStatuses = [
            'Interested', 'Site visit Scheduled', 'Site Visit Done Call', 
            'Follow Up', 'FollowUp Required', 'On Hold', 'Highly Interested', 
            'Call Back Requested', 'Price Discussion'
        ];
        
        // Pending Statuses
        $pendingStatuses = ['Pending', 'pending', 'Pending status'];

        // 2. Aaj jinki call aayi hai, unko Telecaller (assignee) ke hisaab se group kar lena
        $allocations = TelecallerAllocation::with(['assignee', 'customer'])
            ->whereDate('called_at', $today)
            ->get()
            ->groupBy(function($item) {
                return $item->assignee_id;
            });

        $reportText = "📊 *Daily Telecalling & Late Call Report* (" . date('d M Y') . ")\n\n";

        foreach ($allocations as $assigneeId => $calls) {
            $assignee = $calls->first()->assignee;
            $telecallerName = $assignee ? ($assignee->full_name ?? $assignee->member_name ?? 'Unknown') : 'Unknown';
            
            // 3. Counts nikalna
            $connectedCount = $calls->whereIn('call_status', $connectedStatuses)->count();
            $pendingCount = $calls->whereIn('call_status', $pendingStatuses)->count();
            
            // 4. Total Duration (Minutes to Hrs & Mins)
            $totalMinutes = $calls->sum('calling_duration');
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $durationStr = $hours > 0 ? "{$hours} hr {$minutes} min" : "{$minutes} min";

            // 5. Late Calls ki List (Jo step 4 algorithm ne detect ki thi)
            $lateCalls = $calls->where('is_late', 1);

            $reportText .= "👤 *Telecaller:* {$telecallerName}\n";
            $reportText .= "📞 Connected Calls: {$connectedCount}\n";
            $reportText .= "⏳ Pending Calls: {$pendingCount}\n";
            $reportText .= "⏱️ Total Call Duration: {$durationStr}\n";
            
            if ($lateCalls->count() > 0) {
                $reportText .= "🚨 *Late Calls Details:*\n";
                foreach ($lateCalls as $late) {
                    $phone = $late->customer ? $late->customer->mobile : 'N/A';
                    $cTime = Carbon::parse($late->calling_time)->format('h:i A');
                    $cDur = $late->calling_duration;
                    $lateBy = $late->late_by_minutes;
                    
                    // Example format: No: 99xxxxxx | Time: 10:00 AM | Dur: 2m | Late: 3m
                    $reportText .= "   - 📱 {$phone} | Call: {$cTime} | Dur: {$cDur}m | Late By: {$lateBy}m\n";
                }
            } else {
                $reportText .= "✅ Excellent! Koi call late nahi hui.\n";
            }
            
            $reportText .= "-----------------------------------\n";
        }

        // 🔥 YAHAN AAP APNA MAIL YA WHATSAPP KA FUNCTION LAGA SAKTE HAIN 🔥
        // Example: WhatsAppApi::sendMessage($adminNumber, $reportText);
        
        // Abhi ke liye ye storage/logs/laravel.log me print hoga test karne ke liye
        Log::info("Daily Calling Report:\n" . $reportText);
        
        $this->info('Report successfully generated!');
    }
}