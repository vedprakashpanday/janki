<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteAllocation extends Model
{
    use HasFactory;

    protected $table = 'site_incharge_allocations';
    protected $guarded = [];

    // Automatically handles JSON encoding/decoding
    protected $casts = [
        'incharge_types' => 'array',
        'allowed_categories' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function dailyEntries()
    {
        return $this->hasMany(SiteDailyEntry::class, 'site_allocation_id');
    }
}