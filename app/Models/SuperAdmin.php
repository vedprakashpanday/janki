<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Authenticatable use kiya taaki future me multi-guard login bana sakein
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    // Password ko automatically hide karne ke liye
    protected $hidden = [
        'password',
    ];

    // Password ko save hone se pehle auto-hash karne ka standard tareeqa
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admin) {
            if (isset($admin->password)) {
                $admin->password = bcrypt($admin->password);
            }
        });

        static::updating(function ($admin) {
            if ($admin->isDirty('password')) {
                $admin->password = bcrypt($admin->password);
            }
        });
    }
// Tasks jo is Employee ne doosro ko diye
    public function tasksAssigned()
    {
        return $this->morphMany(Task::class, 'assigner');
    }
  
    public function receivesBroadcastNotificationsOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('global.user.admin.' . $this->id);
    }


}