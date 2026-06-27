<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class LeaveApplication extends Model
{
    use HasFactory, SoftDeletes,Notifiable;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'designation_id',
        'user_type',
        'user_id',
        'application_type',
        'start_datetime',
        'end_datetime',
        'duration',
        'reason',
        'proof_attachments',
        'status',
        'approved_duration',
        'remarks',
        'approved_by',
        'rejected_by',
        'resume_datetime',
        'emergency_contact',
        'applied_to',
        'emergency_email',
        'is_paid_leave',
        'approved_start_datetime',
        'approved_end_datetime',
        'approved_resume_datetime'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'duration' => 'decimal:2',
        'approved_duration' => 'decimal:2',
        'proof_attachments' => 'array',
    ];

    // ==========================================
    // 🏢 HIERARCHY RELATIONSHIPS
    // ==========================================

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    // ==========================================
    // 👤 USER RELATIONSHIPS
    // ==========================================

    // Dynamic accessor to get the applicant (Employee ya Member)
    public function applicant()
    {
        if ($this->user_type === 'employee') {
            return $this->belongsTo(Employee::class, 'user_id'); // Ensure 'Employee' is your model name
        } elseif ($this->user_type === 'member') {
            return $this->belongsTo(Member::class, 'user_id'); // Ensure 'Member' is your model name
        }
        return null;
    }

    // Direct relation for Employee specifically
    public function employee()
    {
        // Yahan se ->where('user_type', 'employee') HATA DIYA HAI
        return $this->belongsTo(Employee::class, 'user_id');
    }

    // Direct relation for Member specifically
    public function member()
    {
        // Yahan se ->where('user_type', 'member') HATA DIYA HAI
        return $this->belongsTo(Member::class, 'user_id');
    }

    // ==========================================
    // 🧑‍⚖️ APPROVER/REJECTER RELATIONSHIPS
    // ==========================================

    public function approver()
    {
        // Assuming your auth users/admins are in the 'users' or 'employees' table. 
        // Update 'User::class' to 'Employee::class' or 'Admin::class' as per your auth logic.
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
