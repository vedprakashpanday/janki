<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Ledger extends Model
{
    use HasFactory,Notifiable;
    protected $guarded = [];

    // Branch ke sath relation
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function debitVouchers()
    {
        return $this->hasMany(DebitVoucher::class, 'ledger_id');
    }
}