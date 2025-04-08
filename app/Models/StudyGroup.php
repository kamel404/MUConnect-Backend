<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Post;

class StudyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'study_date',
        'topic'
    ];

    public function post(){
        return $this->belongsTo(Post::class);
    }
}
