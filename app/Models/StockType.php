<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockType extends Model {
    protected $guarded = [];
    public function category() { return $this->belongsTo(StockCategory::class, 'category_id'); }
}