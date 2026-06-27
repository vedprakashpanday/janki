<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class FinePenalty extends Model
{
    use HasFactory,Notifiable;
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Naya relation add karein
public function proofMedia()
{
    return $this->belongsTo(\App\Models\Media::class, 'proof_media_id');
}

}