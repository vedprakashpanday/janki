<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelecallerAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relation: Jis Task ke under ye call assign hui
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    // Relation: Kis customer ko call karni hai
    // Note: Agar interested_customers ka model Customer ya InterestedCustomer hai toh wo likhein
    public function customer()
    {
        return $this->belongsTo(InterestedCustomer::class, 'customer_id');
    }

    // Relation: Kis Phase ke liye call ho rahi hai
    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }

    // Relation: Jisko call karni hai
    public function assignee()
    {
        return $this->morphTo();
    }
}