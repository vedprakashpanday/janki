<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function debitVoucher()
    {
        return $this->belongsTo(DebitVoucher::class, 'debit_voucher_id');
    }

    public function repayments()
    {
        return $this->hasMany(EmployeeLoanRepayment::class, 'employee_loan_id');
    }
}