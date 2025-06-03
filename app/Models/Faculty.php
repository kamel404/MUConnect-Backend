<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'name',
        'description', 
        'abbreviation'
    ];

    // Users that belong to the faculty
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Majors that belong to the faculty
    public function majors()
    {
        return $this->hasMany(Major::class);
    }
}
