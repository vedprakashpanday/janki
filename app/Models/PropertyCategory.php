<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyCategory extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'property_type_id', 'category_name', 'status', 'created_by'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    
    public function propertyType() { return $this->belongsTo(PropertyType::class, 'property_type_id'); }
    public function areas() { return $this->hasMany(PropertyArea::class, 'property_category_id'); }
}