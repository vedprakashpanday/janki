<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyArea extends Model
{
    use HasFactory;

   protected $fillable = ['company_id', 'branch_id', 'property_area_id', 'rate_amount', 'start_date', 'end_date', 'status', 'created_by'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    
    public function category() { return $this->belongsTo(PropertyCategory::class, 'property_category_id'); }
    public function rates() { return $this->hasMany(PropertyRate::class, 'property_area_id'); }
}