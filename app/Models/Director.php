<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Director extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    // Password auto-hashing mechanism
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($director) {
            if (isset($director->password)) {
                $director->password = bcrypt($director->password);
            }
        });

        static::updating(function ($director) {
            if ($director->isDirty('password')) {
                $director->password = bcrypt($director->password);
            }
        });
    }

public function companies()
{
    // Pivot table 'company_director' ko define kiya aur 'role' ko pivot data me liya
    return $this->belongsToMany(Company::class, 'company_director', 'director_id', 'company_id')
                ->withPivot('role')
                ->withTimestamps();
}
    



}