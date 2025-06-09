<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'major_id',
    ];

    /**
     * Get the major that owns the category.
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Get the courses in this category.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'category_course', 'course_category_id', 'course_id');
    }
}
