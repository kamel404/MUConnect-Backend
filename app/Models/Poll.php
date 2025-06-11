<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = [
        'question',
    ];

    public function resourceContent()
    {
        return $this->morphOne(ResourceContent::class, 'contentable');
    }

    public function options()
    {
        return $this->hasMany(PollOption::class);
    }
}
