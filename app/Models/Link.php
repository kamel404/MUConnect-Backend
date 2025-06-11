<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'url',
    ];

    public function resourceContent()
    {
        return $this->morphOne(ResourceContent::class, 'contentable');
    }
}
