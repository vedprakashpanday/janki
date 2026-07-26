<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // 🔥 NAYA: Soft Delete import kiya

class DebitVoucher extends Model
{
    use HasFactory, Notifiable, SoftDeletes; // 🔥 NAYA: Trait use kiya

    protected $guarded = []; 

    // ==========================================
    // 🔗 Relationships (Data Scoping ke liye)
    // ==========================================
    
    // Company ke sath relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Branch ke sath relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Jisne Voucher banaya hai (Employee ya Member)
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'member_id');
    }

    // Jisne Approve/Reject kiya
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'id');
    }
}