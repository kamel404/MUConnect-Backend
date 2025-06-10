<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Resource extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function upvotes()
    {
        return $this->hasMany(Upvote::class);
    }

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class);
    }

    public function savedBy()
    {
        return $this->morphMany(SavedItem::class, 'saveable');
    }
}
