<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = 'resource_media';

    protected $fillable = [
        'resource_id',
        'file_path',
        'media_type',
    ];

    /**
     * Get the parent resource for this media.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Accessor for full file URL (if stored using Storage facade)
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope to filter images only
     */
    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    /**
     * Scope to filter videos only
     */
    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
    }
}
