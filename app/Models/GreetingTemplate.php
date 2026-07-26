<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreetingTemplate extends Model
{
    use HasFactory;

    protected $table = 'greeting_templates';

    protected $guarded = []; // Mass assignment allow karne ke liye
}