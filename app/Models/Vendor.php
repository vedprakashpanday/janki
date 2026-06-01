<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // 🔥 NAYA: Company Relation 🔥
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Virtual attribute for Bank Branch conflict bypass
    protected $appends = ['bank_branch_text'];

    public function getBankBranchTextAttribute()
    {
        return array_key_exists('bank_branch', $this->attributes) ? $this->attributes['bank_branch'] : '';
    }
}