<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{

    use HasFactory;

    protected $fillable = [
        'file_path',
        'mime_type',
        'size',
        'checksum',

    ];

    public function post()
    {
        return $this->belongsToMany(Post::class);
    }

}
