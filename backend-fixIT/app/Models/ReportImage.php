<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportImage extends Model
{
    protected $fillable = [
        'report_id',
        'image_path',
    ];

    protected $appends = ['image_url'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
