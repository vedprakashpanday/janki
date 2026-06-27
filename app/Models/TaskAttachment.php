<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TaskAttachment extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Polymorphic: File kisne upload ki
    public function uploader()
    {
        return $this->morphTo();
    }
}