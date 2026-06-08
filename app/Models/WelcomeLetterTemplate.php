<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomeLetterTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['letter_type', 'title', 'content'];
}