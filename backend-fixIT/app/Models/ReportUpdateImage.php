<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportUpdateImage extends Model
{
    protected $fillable = ['report_update_id', 'image_path'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    public function reportUpdate()
    {
        return $this->belongsTo(ReportUpdate::class);
    }
}