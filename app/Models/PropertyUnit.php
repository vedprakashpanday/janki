<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'phase_id', 'entity_type', 
        'property_type_id', 'property_category_id', 'property_area_id', 
        'unit_number', 'boundaries', 'charge_ids', 'map_coordinates', 
        'status', 'availability_status', 'created_by'
    ];

    protected $casts = [
        'boundaries' => 'array',
        'charge_ids' => 'array',
        'map_coordinates' => 'array', // JSON ko array me cast karega
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function phase() { return $this->belongsTo(Phase::class); }
    public function type() { return $this->belongsTo(PropertyType::class, 'property_type_id'); }
    public function category() { return $this->belongsTo(PropertyCategory::class, 'property_category_id'); }
    public function area() { return $this->belongsTo(PropertyArea::class, 'property_area_id'); }
}