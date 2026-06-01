<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $guarded = [];

    // JSON column ko array mein cast karna zaroori hai
    protected $casts = [
        'company_ids' => 'array',
    ];

    // Ek department mein kai designations hongi
    public function designations()
    {
        return $this->hasMany(Designation::class, 'department_id');
    }
}