<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Purane relationships (agar hain) waise hi rehne dein
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // 🟢 NAYE MASTER RELATIONSHIPS JO ADD KARNE HAIN 🟢
    
    public function category()
    {
        return $this->belongsTo(StockCategory::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(StockType::class, 'type_id');
    }

    public function brand()
    {
        return $this->belongsTo(StockBrand::class, 'brand_id');
    }

    // Naya Incharge Relationship (agar Employee model use karna chahein future me)
    // public function incharge()
    // {
    //     return $this->belongsTo(User::class, 'incharge_id'); // Ya jo bhi aapka employee model ho
    // }
}