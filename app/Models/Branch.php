<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Branch extends Model
{
    use HasFactory,Notifiable;

    protected $fillable = [
        'company_id',        // Ye naya field allow kiya
        'branch_id', 
        'branch_name', 
        'branch_state',      
        'branch_district',   
        'opening_date',      
        'branch_location', 
        'branch_map', 
        'branch_status'
    ];

    // Branch belongs to a Company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}