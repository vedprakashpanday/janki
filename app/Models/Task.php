<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Task extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    // Polymorphic: Kisne task diya (SuperAdmin, Director, Employee)
    public function assigner()
    {
        return $this->morphTo();
    }

    // Polymorphic: Kisko task mila (Employee, Member)
    public function assignee()
    {
        return $this->morphTo();
    }

    // Agar task dynamic module se juda hai
    public function trackingModule()
    {
        return $this->belongsTo(TaskTrackingModule::class, 'tracking_module_id');
    }

    // Task ke replies aur remarks
    public function progressLogs()
    {
        return $this->hasMany(TaskProgressLog::class)->latest();
    }

    // Task par attached files
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    
        }
/**
     * 🔥 AUTO-SYNC LIVE PROGRESS 🔥
     * Ye function live database check karega aur count update karega
     */
 /**
     * 🔥 AUTO-SYNC LIVE PROGRESS 🔥
     * Ye function live database check karega aur count update karega
     */
    public function syncLiveProgress()
    {
        if (!$this->tracking_module_id || $this->target_count <= 0) {
            return $this->achieved_count;
        }

        $newCount = $this->achieved_count;

        if ($this->trackingModule && $this->trackingModule->task_category_name == 'Interested Customer Tracking') {
            $memberId = null;
            
            if ($this->assignee) { 
                $memberId = $this->assignee->member_id;
            }

            if ($memberId) {
                $newCount = \Illuminate\Support\Facades\DB::table('interested_customers')
                    ->where('assigned_telecaller', $memberId)
                    ->where('created_at', '>=', $this->created_at)
                    ->when($this->due_datetime, function($query) {
                        return $query->where('created_at', '<=', $this->due_datetime);
                    })
                    ->count();
            }
        }

        // 🔥 NAYA: Agar count me farq aaya hai toh Progress Log table me entry daalein
        if ($newCount != $this->achieved_count) {
            $difference = $newCount - $this->achieved_count;
            $diffText = $difference > 0 ? "+{$difference}" : "{$difference}";

            \App\Models\TaskProgressLog::create([
                'task_id' => $this->id,
                'actor_type' => $this->assignee_type,
                'actor_id' => $this->assignee_id,
                'log_type' => 'progress_update',
                'message_or_remark' => "System Note: Live Tracking detected {$diffText} new entries. (Total Achieved: {$newCount})",
                'entries_completed' => $difference
            ]);

            $this->update(['achieved_count' => $newCount]);
            
            if ($newCount >= $this->target_count && $this->status !== 'Completed') {
                $this->update(['status' => 'Completed']);
                
                \App\Models\TaskProgressLog::create([
                    'task_id' => $this->id,
                    'actor_type' => $this->assignee_type,
                    'actor_id' => $this->assignee_id,
                    'log_type' => 'progress_update',
                    'message_or_remark' => "System Note: Target of {$this->target_count} achieved! Task Auto-Completed.",
                    'entries_completed' => 0
                ]);
            } elseif ($newCount > 0 && $this->status === 'Pending') {
                $this->update(['status' => 'In-Progress']);
            }
        }

        return $newCount;
    }
    
    // Task kis Phase se juda hai
    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }



}