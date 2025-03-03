<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Major;

class MajorController extends Controller
{

    /**
     * Display a listing of the majors.
     */
    public function index()
    {
        $majors = Major::paginate(10);
        return response()->json($majors);
    }

    /**
     * Show major profile
     */
    public function show($id)
    {
        $major = Major::with('faculty')->findOrFail($id);
        return response()->json($major);
    }

    /**
     * Create major
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|unique:majors,name',
            'abbreviation' => 'required|string|unique:majors,abbreviation',
            'faculty_id'   => 'required|exists:faculties,id',
        ]);

        $major = Major::create($validated);
        return response()->json(['message' => 'Major created successfully', 'major' => $major], 201);
    }


    /**
     * Update major
     */
    public function update(Request $request, $id)
    {
        $major = Major::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string',
            'abbreviation' => 'sometimes|required|string',
            'faculty_id'  => 'sometimes|required|exists:faculties,id',
        ]);

        $major->update($validated);
        return response()->json(['message' => 'Major updated successfully', 'major' => $major]);
    }

    /**
     * Delete major
     */
    public function destroy($id)
    {
        $major = Major::findOrFail($id);
        $major->delete();
        return response()->json(['message' => 'Major deleted successfully']);
    }

    /**
    * Search for majors by query(name and abbreviation).
    */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $majors = Major::where('name', 'LIKE', "%{$query}%")
            ->orWhere('abbreviation', 'LIKE', "%{$query}%")
            ->paginate(10);
    
        return response()->json($majors);
    }

    /**
    * Get all students in a major.
    */
    public function getMajorStudents($id)
    {
        $major = Major::findOrFail($id);
        $students = $major->students()->paginate(10);
        return response()->json($students);
    }
    
}
