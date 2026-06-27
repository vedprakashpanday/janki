<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceTimeWindow extends Model
{
    use HasFactory;

    protected $guarded = []; // Allows mass assignment

    // Relation with Company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Relation with Branch (Will be null for Head Office)
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Relation to track which Admin/Director created/requested this rule
    public function actionBy()
    {
        return $this->belongsTo(Employee::class, 'action_by'); 
    }
}