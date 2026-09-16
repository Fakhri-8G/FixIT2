<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportImage extends Model
{
    use HasFactory;

    protected $fillable = ['report_id', 'image_path'];

    // WAJIB: Biar 'image_url' otomatis masuk ke response JSON
    protected $appends = ['image_url'];

    // Accessor buat nge-generate full URL secara otomatis
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}