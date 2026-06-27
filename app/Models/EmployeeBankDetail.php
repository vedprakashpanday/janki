<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class EmployeeBankDetail extends Model
{
    use Notifiable;
    protected $table = 'tbl_bank_details';
    protected $guarded = [];
}
