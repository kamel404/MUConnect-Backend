<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubMember extends Model
{
    protected $fillable = [
        'club_id',
        'name',
        'picture',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
