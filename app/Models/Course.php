<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\StudyGroup;
use App\Models\Resource;
use App\Models\Major;
use App\Models\CourseCategory;

class Course extends Model
{
    use HasFactory;
    

        protected $fillable = [
        'code',
        'title',
        'credits',
        'year',
        'semester',
        'faculty_id',
        'major_id',
    ];


    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function studyGroups()
    {
        return $this->hasMany(StudyGroup::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    // Legacy: courses previously referenced `majors()` as a collection. Retained for backward compatibility.
    public function majors()
    {
        return $this->hasMany(Major::class);
    }

    /**
     * Get the major that this course belongs to.
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function getRouteKeyName()
    {
        return 'code'; // Use course code for route model binding
    }

        /**
     * Get the faculty that teaches this course.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    /**
     * Get the categories that this course belongs to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CourseCategory::class, 'category_course', 'course_id', 'course_category_id');
    }

    /**
     * Get the prerequisites for this course.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisite',
            'course_id',
            'prerequisite_id'
        );
    }

    /**
     * Get the courses that have this course as a prerequisite.
     */
    public function successorCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisite',
            'prerequisite_id',
            'course_id'
        );
    }

    /**
     * Scope a query to filter courses by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('course_categories.id', $categoryId);
        });
    }

    /**
     * Scope a query to filter courses by major.
     */
    public function scopeInMajor($query, $majorId)
    {
        return $query->whereHas('categories', function ($q) use ($majorId) {
            $q->where('course_categories.major_id', $majorId);
        });
    }

}
