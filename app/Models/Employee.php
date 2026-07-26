<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
// 👇 YEH LINE ADD KAREIN
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasApiTokens,HasFactory, HasRoles,Notifiable;

    protected $guard_name = 'web';

    protected $table = 'adm_regist';
    protected $guarded = [];

    // Naya Relation Add Kiya 👇
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function bankDetails()
    {
        return $this->hasMany(EmployeeBankDetail::class, 'member_id', 'member_id');
    }

    public function salary()
    {
        return $this->hasOne(Salary::class, 'employee_id');
    }

    // Department ka relation
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Nayi Designation ID ka relation
    // Sahi naam: 'designation'
    public function designation() 
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class, 'user_id');
    }


    // Tasks jo is Employee ne doosro ko diye
    public function tasksAssigned()
    {
        return $this->morphMany(Task::class, 'assigner');
    }

    // Tasks jo is Employee ko mile
    public function tasksReceived()
    {
        return $this->morphMany(Task::class, 'assignee');
    }

    // Is Employee ke replies/remarks
    public function taskProgressLogs()
    {
        return $this->morphMany(TaskProgressLog::class, 'actor');
    }

    // Travel Allowances claimed by this employee
    public function travelAllowances()
    {
        return $this->hasMany(TravelAllowance::class, 'employee_id');
    }

    // Travel Allowances approved/rejected by this employee (if they are an admin/ceo)
    public function approvedAllowances()
    {
        return $this->hasMany(TravelAllowance::class, 'approver_id');
    }

    public function receivesBroadcastNotificationsOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('global.user.employee.' . $this->id);
    }

    public function siteAllocations()
{
    // Tasks received as Site Incharge
    return $this->hasMany(SiteAllocation::class, 'employee_id');
}

public function siteDailyEntries()
{
    // Daily reports submitted by this employee
    return $this->hasMany(SiteDailyEntry::class, 'employee_id');
}
}