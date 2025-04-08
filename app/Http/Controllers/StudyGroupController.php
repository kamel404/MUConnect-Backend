<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;

class StudyGroupController extends Controller
{
    public function index() 
    {
        $studyGroups = StudyGroup::with('post')->paginate(10);
        return response()->json($studyGroups);
    }

    public function store(Request $request) 
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'study_date' => 'nullable|date',
            'topic' => 'nullable|string|max:255',
        ]);

        $studyGroup = StudyGroup::create($validated);

        return response()->json(['message' => 'Study Group created successfully', 'Study Group' => $studyGroup], 201);
    }

    public function show($id)
    {
        $study_group = StudyGroup::with('post')->findOrFail($id);
        return response()->json($study_group);
    }

    public function update(Request $request, $id) 
    {
        $validated = $request->validate([
            'study_date' => 'nullable|date',
            'topic' => 'nullable|string|max:255',
        ]);

        $studyGroup = StudyGroup::findOrFail($id);
        $studyGroup->update($validated);

        return response()->json(['message' => 'Study Group updated successfully', 'Study Group' => $studyGroup], 201);
    }

    public function destroy($id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $studyGroup->delete();
        return response()->json(['message' => 'Study Group deleted successfully']);
    }
}
