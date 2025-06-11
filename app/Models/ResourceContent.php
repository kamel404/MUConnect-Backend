<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceContent extends Model
{

    protected $fillable = [
        'resource_id',
        'contentable_type',
        'contentable_id',
        'position',
    ];
    

    public function contentable()
    {
        return $this->morphTo();
    }
}
