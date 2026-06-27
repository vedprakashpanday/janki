<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class InterestedCustomer extends Model
{
    use HasFactory,Notifiable;
    
    protected $guarded = []; // Allows all fields

    // NAYA: Company ke sath relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // PEHLE SE THA: Branch ke sath relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
    /**
     * Helper Scope: Sirf 'active' records fetch karne ke liye
     * Use: InterestedCustomer::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where('entry_status', 'active');
    }

    /**
     * Helper Scope: Sirf 'pending' records fetch karne ke liye
     * Use: InterestedCustomer::pending()->get();
     */
    public function scopePending($query)
    {
        return $query->where('entry_status', 'pending');
    }
}