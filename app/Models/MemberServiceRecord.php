<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberServiceRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'action_details' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id_ref');
    }
}