<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Media extends Model
{
   use Notifiable;
   protected $fillable = ['original_name', 'file_path', 'file_type', 'extension', 'file_size'];
}
