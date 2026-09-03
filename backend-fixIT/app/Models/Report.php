<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'location_id',
        'title',
        'description',
        'image',
        'status'
    ];

    public function user() 
    { 
        return $this->belongsTo(User::class); 
    }
    public function category() 
    { 
        return $this->belongsTo(Category::class); 
    }
    public function location() 
    { 
        return $this->belongsTo(Location::class); 
    }
    public function updates() 
    { 
        return $this->hasMany(ReportUpdate::class); 
    }
}