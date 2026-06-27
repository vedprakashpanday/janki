<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Attendance extends Model
{
    use HasFactory, Notifiable;
    
    protected $guarded = [];

    // Automatically cast JSON data back to Array
    protected $casts = [
        'punch_proof_images' => 'array',
        'is_late_punch' => 'boolean',
    ];

    // Relation with Employee (already assumed based on logic, but good to ensure)
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'member_id');
    }
}