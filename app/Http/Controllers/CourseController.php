<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;


class CourseController extends Controller
{
    public function index()
    {
        // List all courses, with pagination and filters
        $courses = Course::with(['faculty', 'categories'])->paginate(5);
        return response()->json($courses);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'code' => 'required|string|max:10|unique:courses,code',
            'title' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'year' => 'required|integer|min:1',
            'semester' => 'required|string|in:Fall,Spring,Summer',
            'faculty_id' => 'required|exists:faculties,id',
            'major_id' => 'nullable|exists:majors,id',
        ]);

        // Create a new course
        $course = Course::create($request->all());

        // Attach categories if provided
        if ($request->has('categories')) {
            $course->categories()->sync($request->categories);
        }

        return response()->json($course, 201);
    }

    public function show(Course $course)
    {
        // Show a specific course with its categories and prerequisites
        return response()->json($course->load(['faculty', 'categories', 'prerequisites']));
    }

    public function update(Request $request, Course $course)
    {
        // Validate the request data
        $request->validate([
            'code' => 'sometimes|required|string|max:10|unique:courses,code,' . $course->id,
            'title' => 'sometimes|required|string|max:255',
            'credits' => 'sometimes|required|integer|min:1',
            'year' => 'sometimes|required|integer|min:1',
            'semester' => 'sometimes|required|integer|min:1|max:2',
            'faculty_id' => 'sometimes|required|exists:faculties,id',
            'major_id' => 'nullable|exists:majors,id',
        ]);

        // Update the course
        $course->update($request->all());

        // Sync categories if provided
        if ($request->has('categories')) {
            $course->categories()->sync($request->categories);
        }

        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        // Delete a course
        $course->delete();
        return response()->json(null, 204);
    }
}
