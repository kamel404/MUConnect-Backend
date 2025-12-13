<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Major;

class MajorController extends Controller
{

    /**
     * Display a listing of the majors.
     */
    public function index()
    {
        $page = request()->input('page', 1);
        
        // Cache for 1 hour (3600 seconds) - majors rarely change
        $majors = Cache::remember("majors:list:page:{$page}", 3600, function () {
            return Major::paginate(10);
        });
        
        return response()->json($majors);
    }

    /**
     * Show major 
     */
    public function show($id)
    {
        $major = Cache::remember("major:{$id}:with_faculty", 3600, function () use ($id) {
            return Major::with('faculty')->findOrFail($id);
        });
        
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
        
        // Clear majors list cache and faculty majors cache
        Cache::tags(['majors'])->flush();
        Cache::forget("faculty:{$validated['faculty_id']}:majors");
        
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
        
        // Clear cache for this major and majors list
        Cache::forget("major:{$id}:with_faculty");
        Cache::tags(['majors'])->flush();
        
        // Clear faculty majors cache for old and new faculty (if changed)
        Cache::forget("faculty:{$major->faculty_id}:majors");
        if (isset($validated['faculty_id']) && $validated['faculty_id'] != $major->faculty_id) {
            Cache::forget("faculty:{$validated['faculty_id']}:majors");
        }
        
        return response()->json(['message' => 'Major updated successfully', 'major' => $major]);
    }

    /**
     * Delete major
     */
    public function destroy($id)
    {
        $major = Major::findOrFail($id);
        $facultyId = $major->faculty_id;
        $major->delete();
        
        // Clear cache for this major and majors list
        Cache::forget("major:{$id}:with_faculty");
        Cache::tags(['majors'])->flush();
        Cache::forget("faculty:{$facultyId}:majors");
        
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
