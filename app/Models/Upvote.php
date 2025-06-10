<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upvote extends Model
{
    protected $fillable = [
        'user_id',
        'resource_id',
        'upvoteable_type',
        'upvoteable_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function upvoteable()
    {
        return $this->morphTo();
    }
}
