<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SavedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'saveable_id',
        'saveable_type'
    ];

    public function saveable()
    {
        return $this->morphTo();
    }
}
