<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;
    protected $guarded = []; 

    // Branch Relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // API mein ek naya virtual field bhejne ke liye
    protected $appends = ['bank_branch_text'];

    // Ye function asli database column ka text nikalega (object ko ignore karke)
    public function getBankBranchTextAttribute()
    {
        return array_key_exists('branch', $this->attributes) ? $this->attributes['branch'] : '';
    }
}
