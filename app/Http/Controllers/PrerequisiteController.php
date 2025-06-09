<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class PrerequisiteController extends Controller
{

    /**
     * Display a listing of the prerequisites for a course.
     */
    public function index(Course $course)
    {
        // List all prerequisites for a specific course
        $prerequisites = $course->prerequisites()->with('faculty', 'categories')->get();
        return response()->json($prerequisites);
    }

    
    public function addPrerequisite(Request $request, Course $course)
    {
        // Add a prerequisite to a course
        $request->validate([
            'prerequisite_id' => 'required|exists:courses,id',
        ]);

        $course->prerequisites()->attach($request->prerequisite_id);
        return response()->json(null, 204);
    }

    public function removePrerequisite(Request $request, Course $course, $prerequisiteId)
    {
        // Remove a prerequisite from a course
        $course->prerequisites()->detach($prerequisiteId);
        return response()->json(null, 204);
    }
}
