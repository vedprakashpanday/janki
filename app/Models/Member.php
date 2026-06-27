<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

class Member extends Model
{
    use HasFactory,HasRoles,Notifiable;
    protected $guarded = []; 

// Company Relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Department Relation
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Designation Relation
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }


    // Branch Relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // API mein ek naya virtual field bhejne ke liye
    protected $appends = ['bank_branch_text'];

    // Ye function asli database column ka text nikalega (object ko ignore karke)
    public function getBankBranchTextAttribute()
    {
        return array_key_exists('branch', $this->attributes) ? $this->attributes['branch'] : '';
    }


    // Tasks jo is Member ko mile
    public function tasksReceived()
    {
        return $this->morphMany(Task::class, 'assignee');
    }

    // Is Member ke replies
    public function taskProgressLogs()
    {
        return $this->morphMany(TaskProgressLog::class, 'actor');
    }

}
