<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoanRepayment extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function salary()
    {
        return $this->belongsTo(Salary::class, 'salary_id');
    }
}