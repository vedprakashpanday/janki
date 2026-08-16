<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockAttribute extends Model {
    protected $guarded = [];
    public function options() { return $this->hasMany(StockAttributeOption::class, 'attribute_id'); }
    public function categories() { return $this->belongsToMany(StockCategory::class, 'stock_category_attributes', 'attribute_id', 'category_id'); }
}