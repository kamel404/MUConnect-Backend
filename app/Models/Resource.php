<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


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

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class);
    }


    public function hashtags() {
        return $this->belongsToMany(Hashtag::class, 'hashtag_resource');
    }
}
