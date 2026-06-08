<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTrackingModule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'tracking_module_id');
    }
}