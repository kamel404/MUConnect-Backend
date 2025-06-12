<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyGroup extends Model
{


    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'capacity',
        'location',
        'is_online',
        'is_complete',
        'meeting_time',
        'creator_id',
        'course_id',
        'major_id',
        'faculty_id',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_complete' => 'boolean',
        'meeting_time' => 'datetime',
    ];

    // Creator of the group
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Members of the group (including creator)
    public function members()
    {
        return $this->belongsToMany(User::class, 'study_group_user')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }

    // Admins of the group (includes creator)
    public function admins()
    {
        return $this->belongsToMany(User::class, 'study_group_user')
                    ->wherePivot('is_admin', true)
                    ->withTimestamps();
    }

    // Related major
    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    // Related faculty
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    // Related course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Check if group is full
    public function isFull(): bool
    {
        return $this->capacity !== null && $this->members()->count() >= $this->capacity;
    }

    // Get current member count
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function savedBy()
    {
        return $this->morphMany(\App\Models\SavedItem::class, 'saveable');
    }
}