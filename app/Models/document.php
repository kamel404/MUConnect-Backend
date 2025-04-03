<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class document extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_path',
        'mime_type',
        'size',
        'visibility',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
    