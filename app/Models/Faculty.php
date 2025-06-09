<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =
    [
        'name',
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

    // Courses that belong to the faculty
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
