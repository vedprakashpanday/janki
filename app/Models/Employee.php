<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'adm_regist'; // Map to your table
    protected $guarded = []; // Allow all fields

    // Relate to Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Relate to Bank
    public function bankDetails()
    {
        return $this->hasOne(EmployeeBankDetail::class, 'member_id', 'member_id');
    }

    public function salary()
    {
        return $this->hasOne(Salary::class, 'employee_id');
    }
   
}
