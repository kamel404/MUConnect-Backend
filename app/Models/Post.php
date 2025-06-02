<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Attachment;


class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function attachment()
    {
        return $this->belongsToMany(Attachment::class);
        }
    
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}

//todo I MAY USE THOSE FOR VALIDATION AND ATTACHMENTS IN THE FUTURE 
// public function attachments(): HasMany
// {
//     return $this->hasMany(PostAttachment::class)->orderBy('sort_order');
// }

// public function images(): HasMany
// {
//     return $this->hasMany(PostAttachment::class)->where('type', 'image');
// }

// public function videos(): HasMany
// {
//     return $this->hasMany(PostAttachment::class)->where('type', 'video');
// }

// public function documents(): HasMany
// {
//     return $this->hasMany(PostAttachment::class)->where('type', 'document');
// }

// public function links(): HasMany
// {
//     return $this->hasMany(PostAttachment::class)->where('type', 'link');
// }

// public function scopePublished($query)
// {
//     return $query->where('status', 'published');
// }

// public function scopeDraft($query)
// {
//     return $query->where('status', 'draft');
// }

// public function publish()
// {
//     $this->update([
//         'status' => 'published',
//         'published_at' => now(),
//     ]);
// }