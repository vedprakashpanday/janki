<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    // Table name define kiya
    protected $table = 'tbl_bank_details';

    // Mass assignable fields
    protected $fillable = [
        'member_id',
        'account_name',
        'account_no',
        'account_type',
        'bank_name',
        'branch',
        'ifsc_code',
        'status'
    ];
}