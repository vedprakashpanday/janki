<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Agent extends Model
{
    use HasFactory,Notifiable;
    protected $guarded = []; // Shortcut to allow all fields

    // Company Relation
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    
    // Branch se relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}