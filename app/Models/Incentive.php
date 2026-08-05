<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incentive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 
        'branch_id', 
        'department_id', 
        'designation_id', 
        'emp_id',
        'incentive_type_id', 
        'passbook_no', 
        'net_amount', 
        'calc_type', 
        'dist_type',
        'value', 
        'calculated_amount', 
        'dv_no', 
        'paid', 
        'total_paid', 
        'left', 
        'total_left',
        'incentive_status', 
        'created_by', 
        'updated_by'
    ];

    // Relations
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

    // Assuming your employees table model is named Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id','member_id'); 
    }

    public function type()
    {
        return $this->belongsTo(IncentiveType::class, 'incentive_type_id');
    }
}