<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    // Table ka exact naam define karna zaroori hai
    protected $table = 'tbl_bank_details';

    // Mass assignment permissions (Jo columns hum form se insert kar rahe hain)
    protected $fillable = [
        'company_id',
        'branch_id',
        'member_id',
        'account_name',
        'account_no',
        'account_type',
        'bank_name',
        'branch',
        'ifsc_code',
        'status',
        'created_by'
    ];
}