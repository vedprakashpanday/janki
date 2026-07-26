<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempReceipt extends Model
{
    use HasFactory;

    protected $table = 'temp_receipts';

    protected $guarded = [];

    // JSON ko automatic array mein convert karne ke liye
    protected $casts = [
        'amount_details' => 'array',
        'receipt_date' => 'date',
        'date_of_cheque' => 'date',
        'transaction_date' => 'date',
    ];

    // --- Relationships ---
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Agar Phase model ban chuka hai toh
    // public function phase()
    // {
    //     return $this->belongsTo(Phase::class, 'phase_id');
    // }

    public function approvedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'approved_by_emp_id');
    }

    public function authorizedCeo()
    {
        return $this->belongsTo(SuperAdmin::class, 'auth_ceo_id');
    }

    // TempReceipt.php mein ye relation add karein
public function receivedByEmployee()
{
    // 'received_by_emp_code' hamara column hai aur 'member_id' adm_regist ka column
    return $this->belongsTo(Employee::class, 'received_by_emp_code', 'member_id');
}
}