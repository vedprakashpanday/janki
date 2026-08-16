<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteVisitImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function siteVisit()
    {
        return $this->belongsTo(SiteVisit::class, 'site_visit_id');
    }
}