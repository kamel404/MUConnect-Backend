<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'resource_id',
        'url',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
