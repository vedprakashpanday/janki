<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitVoucher extends Model
{
    // protected $table = 'debit_vouchers'; // By default Laravel yehi name leta hai
    protected $guarded = []; // Saare columns ko save hone ki permission
}