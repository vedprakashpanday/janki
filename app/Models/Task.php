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

        
}