<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionRequest extends Model
{
        protected $fillable = [
        'requester_id', 'course_name', 'current_section', 'desired_section',
        'current_day', 'desired_day', 'current_time', 'desired_time', 'reason', 'status'
    ];

    public function requester() {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function applications() {
        return $this->hasMany(Application::class);
    }
}
