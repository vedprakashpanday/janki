<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAction extends Model
{
    use HasFactory;

    protected $fillable = ['action_name', 'action_slug', 'status'];
}