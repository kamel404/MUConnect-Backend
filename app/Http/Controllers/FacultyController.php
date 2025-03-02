<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faculty;

class FacultyController extends Controller
{
    /**
     * Display a listing of the faculties.
    */
    public function index()
    {
        $faculties = Faculty::all();
        return response()->json($faculties);
    }

    /**
    * Store a newly created faculty in storage.
    */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'         => 'required|string|unique:faculties,name',
            'description'  => 'string', // optional
            'abbreviation' => 'required|string|unique:faculties,abbreviation',
        ]);

        $faculty = Faculty::create($validatedData);

        return response()->json(['message' => 'Faculty created successfully', 'faculty' => $faculty], 201);
    }

    /**
    * Display the specified faculty.
    */
    public function show($id)
    {
        $faculty = Faculty::findOrFail($id);
        return response()->json($faculty);
    }

    /**
    * Update the specified faculty in storage.
    */
    public function update(Request $request, $id)
    {
        $faculty = Faculty::findOrFail($id);

        $validatedData = $request->validate([
            'name'         => 'sometimes|required|string|unique:faculties,name,' . $faculty->id,
            'description'  => 'sometimes|required|string',
            'abbreviation' => 'sometimes|required|string|unique:faculties,abbreviation,' . $faculty->id,
        ]);

        $faculty->update($validatedData);

        return response()->json(['message' => 'Faculty updated successfully', 'faculty' => $faculty]);
    }

    /**
    * Remove the specified faculty from storage.
    */
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->delete();

        return response()->json(['message' => 'Faculty deleted successfully']);
    }

    /**
    * Search for a name
    */
    public function search($name)
    {
        $faculty = Faculty::where('name', 'like', '%' . $name . '%')->get();
        return response()->json($faculty);
    }

    /**
    * Search for an abbreviation
    */
    public function searchAbbreviation($abbreviation)
    {
        $faculty = Faculty::where('abbreviation', 'like', '%' . $abbreviation . '%')->get();
        return response()->json($faculty);
    }
}
