<?php

namespace App\Models;

// 1. Ise import karna na bhoolein
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // 2. Yahan 'HasApiTokens' add karein
    use HasApiTokens, HasFactory, Notifiable;
use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',     // Aapne phone aur role bhi add kiya tha, toh unhe fillable me daal lijiye
        'role',
        'is_active',
        'password',
    ];

    


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

// Tasks jo is Employee ne doosro ko diye
    public function tasksAssigned()
    {
        return $this->morphMany(Task::class, 'assigner');
    }
  
   // 🔥 Admin portal ke global channel se jodne ke liye
   public function receivesBroadcastNotificationsOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('global.user.admin.' . $this->id);
    }
}
