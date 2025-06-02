<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyGroupController extends Controller
{
    public function index()
    {
        return StudyGroup::with(['user', 'course'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $studyGroup = StudyGroup::create([
            'user_id' => Auth::id(),
            'course_id' => $validated['course_id'],
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json($studyGroup, 201);
    }

    public function show($id)
    {
        return StudyGroup::with(['user', 'course'])->findOrFail($id);
    }

    public function destroy($id)
    {
        $studyGroup = StudyGroup::findOrFail($id);

        if ($studyGroup->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $studyGroup->delete();

        return response()->json(['message' => 'Study group deleted']);
    }
}
