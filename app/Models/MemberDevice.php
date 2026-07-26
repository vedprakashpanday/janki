<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDevice extends Model
{
    use HasFactory;

    protected $guarded = []; // Mass assignment allow karne ke liye

   public function member()
    {
        // Ab 'member_id' column, Member table ke 'id' se match hoga
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
    public function sessions()
    {
        return $this->hasMany(MemberLoginSession::class, 'member_device_id', 'id');
    }
}