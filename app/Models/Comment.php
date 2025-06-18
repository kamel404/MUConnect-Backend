<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'body',
        'commentable_type',
        'commentable_id',
    ];

    public function user()
    {   
        return $this->belongsTo(User::class);
    }

    public function hashtags() {
        return $this->belongsToMany(Hashtag::class, 'hashtag_comment');
    }

    public function commentable() {
        return $this->morphTo();
    }

    public function mentions() {
        return $this->hasMany(Mention::class);
    }
    
    public function upvotes() {
        return $this->morphMany(Upvote::class, 'upvoteable');
    }
}
