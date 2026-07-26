<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TaskProgressLog extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Polymorphic: Kisne reply/remark diya
    public function actor()
    {
        return $this->morphTo();
    }

    // 🔥 YEH NAYA FUNCTION ADD KARNA HAI 🔥
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_progress_log_id');
    }
}