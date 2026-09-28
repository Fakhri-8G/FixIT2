<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportFeedback extends Model
{
    // Wajib eksplisit: Laravel menganggap "feedback" itu uncountable (tidak punya bentuk jamak),
    // jadi nama tabel tebakan otomatisnya "report_feedback", bukan "report_feedbacks"
    protected $table = 'report_feedbacks';

    protected $fillable = ['report_id', 'user_id', 'type', 'rating', 'comment'];

    public function report() { return $this->belongsTo(Report::class); }
    public function user() { return $this->belongsTo(User::class); }
}