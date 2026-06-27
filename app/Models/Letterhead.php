<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Letterhead extends Model
{
    use HasFactory,Notifiable;
    protected $guarded = [];

    // Branch Relation (Nullable for Head Office)
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // NAYA: Company Relation (Global ya specific company ke liye)
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}