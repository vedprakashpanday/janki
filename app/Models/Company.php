<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $guarded = []; // Sab allow karne ke liye

    // Parent company fetch karne ke liye (Jaise Janki Villa ka parent Amitabh Developers hai)
    public function parent()
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    // Ek company ki kitni child companies hain wo dekhne ke liye
    public function children()
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    // Future use ke liye: Ek company me kitne branches hain
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function directors()
{
    // Yahan ulta relation
    return $this->belongsToMany(Director::class, 'company_director', 'company_id', 'director_id')
                ->withPivot('role')
                ->withTimestamps();
}
}