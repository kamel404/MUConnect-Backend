<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ResourceReport;

class Resource extends Model
{
    public function savedBy()
    {
        return $this->morphMany(\App\Models\SavedItem::class, 'saveable');
    }

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'course_id',
        'major_id',
        'faculty_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function upvotes()
    {
        return $this->morphMany(Upvote::class, 'upvoteable');
    }

    public function polls()
    {
        return $this->hasOne(Poll::class);
    }

    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function major()
    {
        return $this->belongsTo(\App\Models\Major::class);
    }

    public function faculty()
    {
        return $this->belongsTo(\App\Models\Faculty::class);
    }

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class);
    }

    public function hashtags() {
        return $this->belongsToMany(Hashtag::class, 'hashtag_resource');
    }

    /**
     * Check if a user has upvoted this resource
     *
     * @param int $userId
     * @return bool
     */
    public function isUpvotedByUser($userId)
    {
        return $this->upvotes()->where('user_id', $userId)->exists();
    }

    public function reports()
    {
        return $this->hasMany(ResourceReport::class);
    }

    public function openReports()
    {
        return $this->hasMany(ResourceReport::class)->open();
    }
}
