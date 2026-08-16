<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockAttributeOption extends Model {
    protected $guarded = [];
    public function attribute() { return $this->belongsTo(StockAttribute::class, 'attribute_id'); }
}