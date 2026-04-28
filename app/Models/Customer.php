<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
   use HasFactory;
    
    // Sab fields ko allow karne ke liye guarded empty chhod dete hain (Shortcut for large forms)
    protected $guarded = [];

    // NAYA: Branch ke sath relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
