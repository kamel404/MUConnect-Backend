<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    protected $fillable = [
        'name',
        'code'
    ];

    public function users()
    {
        return $this->hasMany(User::class);

    }
    public function Faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
