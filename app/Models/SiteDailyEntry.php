<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteDailyEntry extends Model
{
    use HasFactory;

    protected $table = 'site_daily_entries';
    protected $guarded = [];

    // Magic feature for your dynamic fields
    protected $casts = [
        'entry_details' => 'array',
        'entry_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(SiteAllocation::class, 'site_allocation_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Connects to the polymorphic documents table
    public function documents()
    {
        return $this->morphMany(SiteEntryDocument::class, 'documentable');
    }
}
