<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Attachment extends Model
{

    use HasFactory;

    protected $fillable = [
        'file_path',
        'mime_type',
        'size',
        'checksum',
        'url',
    ];
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

}
