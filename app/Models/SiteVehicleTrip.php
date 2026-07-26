<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteVehicleTrip extends Model
{
    use HasFactory;

    // 🔥 FIX: Ye line add karni hai
    protected $guarded = []; 

    // ... aapke baaki ke relations yahan honge
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}