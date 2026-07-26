<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberLocationLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relation with Member table
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}