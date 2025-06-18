<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'file_path',
        'file_type',
        'mime_type',
        'size',
        'checksum',
        'url',
    ];
 
    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }

    /**
     * Accessor for full URL (auto-used with $attachment->url)
     */
    public function getUrlAttribute($value)
    {
        // If it's already a full URL (e.g., from S3), use it
        if ($value && str_starts_with($value, 'http')) {
            return $value;
        }

        return Storage::url($this->file_path);
    }

}
