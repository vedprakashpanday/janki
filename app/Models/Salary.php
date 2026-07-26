<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;
    protected $guarded = [];

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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator()
    {
        // For 'Checked By' tracking
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function approver()
    {
        // For Maker-Checker Approval
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function loanRepayment()
    {
        return $this->hasOne(EmployeeLoanRepayment::class, 'salary_id');
    }
}