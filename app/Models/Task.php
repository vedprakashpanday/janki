<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

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
    public function syncLiveProgress()
    {
        if (!$this->tracking_module_id || $this->target_count <= 0) {
            return $this->achieved_count;
        }

        $newCount = $this->achieved_count;

        if ($this->trackingModule && $this->trackingModule->task_category_name == 'Interested Customer Tracking') {
            $memberId = null;
            
            // 🔥 FIX: Agar user delete ho gaya hai toh null check lagana zaruri hai
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

        if ($newCount != $this->achieved_count) {
            $this->update(['achieved_count' => $newCount]);
            
            if ($newCount >= $this->target_count && $this->status !== 'Completed') {
                $this->update(['status' => 'Completed']);
            }
        }

        return $newCount;
    }
        
}