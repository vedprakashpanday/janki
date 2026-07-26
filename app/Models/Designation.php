<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Designation extends Model
{
    use HasFactory,Notifiable;

    protected $fillable = [
        'designation_code', 
        'designation_name', 
        'department_id', // Naya field add kiya
        'status',
        'position',                  // Naya Add Kiya
    'plot_commission',           // Naya Add Kiya
    'construction_commission'
    ];

    // Purana $casts array jisme company_ids tha, use hata dijiye.

    // Relation add karein
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}