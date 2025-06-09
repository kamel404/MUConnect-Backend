<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseCategory;
use App\Models\Major;

class CourseCategoryController extends Controller
{
    /**
     * Display a listing of the course categories.
     */
    public function index()
    {
        // List all course categories, with pagination
        $categories = CourseCategory::with('major')->paginate(15);
        return response()->json($categories);
    }

    /**
     * Store a new course category.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'major_id' => 'required|exists:majors,id',
        ]);

        // Create a new course category
        $category = CourseCategory::create($request->all());
        return response()->json($category, 201);
    }

    /**
     * Show a specific course category.
     */
    public function show(CourseCategory $category)
    {
        // Show a specific course category with its major
        return response()->json($category->load('major'));
    }

    /**
     * Update an existing course category.
     */
    public function update(Request $request, CourseCategory $category)
    {
        // Validate the request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'major_id' => 'sometimes|required|exists:majors,id',
        ]);

        // Update the course category
        $category->update($request->all());
        return response()->json($category);
    }

    /**
     * Remove a course category.
     */
    public function destroy(CourseCategory $category)
    {
        // Delete the course category
        $category->delete();
        return response()->json(null, 204);
    }

    // Get categories by major
    public function getByMajor(Major $major)
    {
        return response()->json($major->categories);
    }
}
