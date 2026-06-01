<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landowner extends Model
{
    use HasFactory;
    protected $guarded = []; 

    // JSON Casting
    protected $casts = [
        'khesra_no' => 'array',
        'khata' => 'array',
        'rakuwa' => 'array',
        'chauhaddi' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // 🔥 NAYA: Company Relation 🔥
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // JSON Casting ke theek neeche ye add karein
    protected $appends = ['bank_branch_text'];

    public function getBankBranchTextAttribute()
    {
        return array_key_exists('branch', $this->attributes) ? $this->attributes['branch'] : '';
    }


}