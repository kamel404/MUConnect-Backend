<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    protected $fillable = [
        'tag_name',
    ];

    public function resources() {
        return $this->belongsToMany(Resource::class, 'hashtag_resource');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'hashtag_comment');
    }
}
