<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 NAYA: SoftDeletes Import

class Customer extends Model
{
    use HasFactory, Notifiable, SoftDeletes; // 🔥 NAYA: trait add kiya
    
    protected $guarded = [];

    // Branch Relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Company Relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // 🔥 NAYA: Master Timeline Relation 🔥
    // Ek customer_code se uski saari records fetch karne ke liye
    public function timelineRecords()
    {
        return $this->hasMany(CustomerRecord::class, 'customer_code', 'customer_code');
    }

    // Virtual attribute for Bank Branch conflict bypass
    protected $appends = ['bank_branch_text'];

    public function getBankBranchTextAttribute()
    {
        return array_key_exists('branch', $this->attributes) ? $this->attributes['branch'] : '';
    }
}