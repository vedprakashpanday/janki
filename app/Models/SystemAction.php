<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SystemAction extends Model
{
    use HasFactory,Notifiable;

    protected $fillable = ['action_name', 'action_slug', 'status'];
}