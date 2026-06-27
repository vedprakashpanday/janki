<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class EmployeeLogin extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    // JSON aur Dates ko automatically handle karne ke liye
    protected $casts = [
        'other_devices' => 'array',
        'blocked_devices' => 'array',
        'otp_time_till' => 'datetime',
    ];

    // Employee table se relation (Taaki naam wagera nikal sakein)
    // Aapki employee table ka jo bhi Model naam ho (jaise Employee ya AdmRegist), wo yahan likhiyega
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'member_id'); 
    }
}