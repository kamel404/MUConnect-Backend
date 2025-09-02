<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIContent extends Model
{
    protected $table = 'ai_contents'; // Explicitly set table name

    protected $fillable = [
        'type',
        'resource_id',
        'attachment_id',
        'user_id',
        'parameters',
        'content',
        'status',
        'error_message',
        'completed_at'
    ];

    protected $casts = [
        'parameters' => 'array',
        'content' => 'array',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted($content = null): void
    {
        $this->update([
            'status' => 'completed',
            'content' => $content,
            'completed_at' => now()
        ]);
    }

    public function markAsFailed($errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }
}
