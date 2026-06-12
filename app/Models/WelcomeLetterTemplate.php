<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomeLetterTemplate extends Model
{
    use HasFactory;

    // Naye columns ko fillable me add kar diya
    protected $fillable = [
        'letter_type', 
        'entity_type', 
        'entity_id', 
        'title', 
        'content'
    ];
}