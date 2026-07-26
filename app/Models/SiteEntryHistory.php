<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SiteEntryHistory extends Model
{
    protected $guarded = [];
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function editor() {
        return $this->belongsTo(Employee::class, 'edited_by_id');
    }
}