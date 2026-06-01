<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
// 👇 YEH LINE ADD KAREIN
use Laravel\Sanctum\HasApiTokens;

class Employee extends Model
{
    use HasApiTokens,HasFactory, HasRoles;

    protected $guard_name = 'web';

    protected $table = 'adm_regist';
    protected $guarded = [];

    // Naya Relation Add Kiya 👇
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function bankDetails()
    {
        return $this->hasOne(EmployeeBankDetail::class, 'member_id', 'member_id');
    }

    public function salary()
    {
        return $this->hasOne(Salary::class, 'employee_id');
    }

    // Department ka relation
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Nayi Designation ID ka relation
    // Sahi naam: 'designation'
    public function designation() 
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }
}