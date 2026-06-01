<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation_code', 
        'designation_name', 
        'department_id', // Naya field add kiya
        'status'
    ];

    // Purana $casts array jisme company_ids tha, use hata dijiye.

    // Relation add karein
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}