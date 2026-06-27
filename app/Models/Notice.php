<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
class Notice extends Model
{
    use HasFactory,Notifiable;

    // Mass assignment bypass
    protected $guarded = []; //[cite: 25]

    public function replies()
    {
        return $this->hasMany(NoticeReply::class, 'notice_id')->orderBy('created_at', 'asc'); //[cite: 25]
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id'); //[cite: 25]
    }

    // New Hierarchical Relations for Targeting
    public function targetCompany()
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }

    public function targetBranch()
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    public function targetDepartment()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function holiday()
{
    return $this->hasOne(Holiday::class, 'notice_id');
}

}