<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Post;

class StudyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'major',
        'course_code',
        'description',
        'members',
        'capacity',
        'location',
        'is_online',
        'is_complete',
        'meeting_time',
        'leader_id',
        'major_id',
        'course_id',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_complete' => 'boolean',
        'meeting_time' => 'datetime',
    ];

    // 🔗 Leader of the group
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    // 🔗 Users who joined this group
    public function members()
    {
        return $this->belongsToMany(User::class, 'study_group_user')->withTimestamps();
    }

    // 🔗 Related major
    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    // 🔗 Related course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // ✅ Check if group is full
    public function isFull(): bool
    {
        return $this->capacity !== null && $this->members()->count() >= $this->capacity;
    }

    // ✅ Check if the group is online
    public function isOnline(): bool
    {
        return $this->is_online === true;
    }
}
