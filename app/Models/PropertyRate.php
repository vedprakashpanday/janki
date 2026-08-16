<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyRate extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'property_area_id', 'rate_amount', 'status', 'created_by'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    
    public function area() { return $this->belongsTo(PropertyArea::class, 'property_area_id'); }
}