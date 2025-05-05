<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSwapApplication extends Model
{
    protected $fillable = [
        'student_id', 'course_id', 'current_section', 'desired_section',
        'current_day', 'current_time', 'desired_day', 'desired_time',
        'reason', 'status'
    ];

    public function student() {
        return $this->belongsTo(User::class);
    }

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function applications() {
        return $this->hasMany(SectionSwapApplication::class, 'request_id');
    }
}
