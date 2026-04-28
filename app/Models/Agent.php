<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;
    protected $guarded = []; // Shortcut to allow all fields

    // Branch se relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}