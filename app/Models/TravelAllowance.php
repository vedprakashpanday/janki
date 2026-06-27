<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TravelAllowance extends Model
{
    use HasFactory,Notifiable;

    protected $table = 'travel_allowances';

    protected $guarded = ['id']; // Allow mass assignment for all other fields

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    // The employee who requested the TA
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // The person (admin/ceo) who approved or rejected the TA
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}