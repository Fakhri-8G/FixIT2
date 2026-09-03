<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportUpdate extends Model
{
    protected $fillable = [
        'report_id',
        'admin_id',
        'status',
        'note'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
