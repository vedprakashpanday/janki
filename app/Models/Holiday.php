<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $guarded = [];

    public function notice()
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }
}