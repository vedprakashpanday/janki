<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $guarded = [];

    // JSON columns ko array mein cast karna zaroori hai
    protected $casts = [
        'company_ids' => 'array',
        'branch_ids'  => 'array', // 🔥 NAYA ADD KIYA
    ];

    public function designations()
    {
        return $this->hasMany(Designation::class, 'department_id');
    }
}