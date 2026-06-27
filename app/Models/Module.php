<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Module extends Model
{
    use HasFactory,Notifiable;

    protected $guarded = [];

    // Ek module ka ek parent ho sakta hai
    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    // Ek parent module ke andar kai sub-menus ho sakte hain
    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('sequence', 'asc');
    }
}