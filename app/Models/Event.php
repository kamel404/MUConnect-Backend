<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'topic',
        'event_date',
        'location',
    ];

    // Each Event belongs to one Post
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
