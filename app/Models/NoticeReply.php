<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class NoticeReply extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    public function notice()
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }
}