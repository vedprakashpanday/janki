<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDesignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',   // Naya
        'branch_id',    // Naya
        'designation_code', 
        'designation_name', 
        'commission_percentage',
        'status'
    ];

    // NAYA: Branch & Company Relations
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}