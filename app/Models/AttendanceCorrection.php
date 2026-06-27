<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $guarded = []; // Allow mass assignment

    // Employee jiska attendance change hua
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'id');
    }

    // Admin jisne change kiya
    public function modifiedBy()
    {
        return $this->belongsTo(Employee::class, 'action_by', 'id');
    }
}