<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelecallerAccess extends Model
{
    use HasFactory;
    protected $fillable = ['staff_id'];
}