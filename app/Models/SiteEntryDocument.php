<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteEntryDocument extends Model
{
    use HasFactory;

    protected $table = 'site_entry_documents';
    protected $guarded = [];

    public function documentable()
    {
        return $this->morphTo();
    }
}