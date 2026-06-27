<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoTaskSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Jisko task milega
    public function assignee()
    {
        return $this->morphTo();
    }

    // Phase ka relation
    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }
}