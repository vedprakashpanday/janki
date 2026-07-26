<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Company ke sath relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Branch ke sath relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Customer detail link karne ke liye
    public function customerDetails()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}