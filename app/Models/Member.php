<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 1. Ye add karo
use Illuminate\Foundation\Auth\User as Authenticatable; // 2. Ye class add karo

// 3. 'Model' ki jagah 'Authenticatable' ko extend karo
class Member extends Authenticatable
{
    // 4. HasApiTokens trait use karo
    use HasApiTokens, HasFactory, HasRoles, Notifiable;
    protected $guard_name = 'web';

    protected $guarded = [];

    protected $casts = [
        'transferred_to' => 'array',
    ];

    public function serviceRecords()
    {
        return $this->hasMany(MemberServiceRecord::class, 'member_id_ref');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    protected $appends = ['bank_branch_text'];

    public function getBankBranchTextAttribute()
    {
        return array_key_exists('branch', $this->attributes) ? $this->attributes['branch'] : '';
    }

    public function tasksReceived()
    {
        return $this->morphMany(Task::class, 'assignee');
    }

    public function taskProgressLogs()
    {
        return $this->morphMany(TaskProgressLog::class, 'actor');
    }

    public function children()
    {
        return $this->hasMany(Member::class, 'sponsor_id', 'member_id');
    }
}
