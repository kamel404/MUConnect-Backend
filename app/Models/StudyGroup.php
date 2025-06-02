<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudyGroup extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'name',
        'description', 
        'course_id',
        'user_id',//creatorID
        'meeting_time',
        'meeting_location',
    ];

    // Users that belong to the faculty
    public function users()
    {
        return $this->belongsTo(User::class);
    }

    // // Majors that belong to the faculty
    // public function majors()
    // {
    //     return $this->hasMany(Major::class);
    // }
    public function courses()
    {
        return $this->belongsTo(Course::class);
    }
}
