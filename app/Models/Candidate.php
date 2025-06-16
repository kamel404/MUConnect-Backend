<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = ['name', 'club_id'];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
