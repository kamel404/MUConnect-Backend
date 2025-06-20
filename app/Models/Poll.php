<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = [
        'question',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function options()
    {
        return $this->hasMany(PollOption::class);
    }
}
