<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEmiPlan extends Model
{
    use HasFactory;

    protected $fillable = [
    'company_id', 'branch_id', 'phase_id', 'plan_name', 
    'emi_tenure', 'rate_discount_per_sqft', 'downpayment_percentage', 
    'start_date', 'end_date', 'status', 'created_by'
];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function phase() { return $this->belongsTo(Phase::class); }
}