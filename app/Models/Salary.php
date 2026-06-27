<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Salary extends Model
{
    use HasFactory,Notifiable;

    protected $fillable = [
        'employee_id', 
        'amount', 
        'basic_pay', 
        'hra', 
        'da', 
        'medical_allowance', 
        'travel_allowance', 
        'other_allowance'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}