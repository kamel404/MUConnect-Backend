<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class document extends Model
{
    
    use HasFactory;
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
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
}
    