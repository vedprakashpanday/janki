<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'phase_id', 'type_name', 'status', 'created_by'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function phase() { return $this->belongsTo(Phase::class, 'phase_id'); }
    
    public function categories() { return $this->hasMany(PropertyCategory::class, 'property_type_id'); }
}