<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockCategory extends Model {
    protected $guarded = [];
    public function types() { return $this->hasMany(StockType::class, 'category_id'); }
    public function attributes() { return $this->belongsToMany(StockAttribute::class, 'stock_category_attributes', 'category_id', 'attribute_id'); }
}