<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'phase_name',
        'phase_location',
        'phase_details',
        'phase_image',
        'phase_google_map_url',
        'created_by'
    ];

    // Aap apne Company aur Branch models ke sath relationships yahan define kar sakte hain
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}