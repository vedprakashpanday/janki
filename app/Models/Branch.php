<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

   protected $fillable = [
        'branch_id', 
        'branch_name', 
        'branch_state',      // Naya field
        'branch_district',   // Naya field
        'opening_date',      // Naya field
        'branch_location', 
        'branch_map', 
        'branch_status'
    ];

  
}