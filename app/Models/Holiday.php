<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class Holiday extends Model
{
    use Notifiable;
    protected $guarded = [];

    public function notice()
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }
}