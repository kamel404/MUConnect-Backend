<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'file_path',
        'file_type',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function resourceContent()
    {
        return $this->morphOne(ResourceContent::class, 'contentable');
    }
}
