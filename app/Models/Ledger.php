<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Ledger extends Model
{
    use HasFactory, Notifiable;
    protected $guarded = [];

    // branch_id hata diya gaya hai, relation removed.

    public function debitVouchers()
    {
        return $this->hasMany(DebitVoucher::class, 'ledger_id');
    }


    // In Ledger.php
    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }



}