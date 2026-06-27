<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DebitVoucher extends Model
{
    use HasFactory,Notifiable;
    // protected $table = 'debit_vouchers'; // By default Laravel yehi name leta hai
    protected $guarded = []; // Saare columns ko save hone ki permission
}