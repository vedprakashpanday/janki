<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
   protected $fillable = ['original_name', 'file_path', 'file_type', 'extension', 'file_size'];
}
