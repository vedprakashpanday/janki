<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAttendance extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relation with Member table
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    // Jis Admin/HR ne attendance correct ki hai, uska relation (optional, useful for logs)
    public function corrector()
    {
        return $this->belongsTo(User::class, 'corrected_by'); 
    }
}