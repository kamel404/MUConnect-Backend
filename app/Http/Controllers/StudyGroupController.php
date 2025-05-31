<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = StudyGroup::with(['leader', 'major', 'course']);

        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('group_name', 'like', "%$keyword%")
                    ->orWhere('major', 'like', "%$keyword%")
                    ->orWhere('course_code', 'like', "%$keyword%")
                    ->orWhereHas('course', function ($q2) use ($keyword) {
                        $q2->where('name', 'like', "%$keyword%");
                    });
            });
        }

        if ($request->has('is_online')) {
            $query->where('is_online', $request->boolean('is_online'));
        }

        if ($request->has('is_complete')) {
            $query->where('is_complete', $request->boolean('is_complete'));
        }

        // Add other filters like major_id, course_id, capacity, etc.

        $perPage = $request->input('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        $group = StudyGroup::with(['leader', 'major', 'course'])->findOrFail($id);
        return response()->json($group);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'major' => 'required|string',
            'course_code' => 'required|string',
            'description' => 'nullable|string',
            'members' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|required_if:is_online,false',
            'is_online' => 'required|boolean',
            'is_complete' => 'boolean',
            'meeting_time' => 'required|date',
            'leader_id' => 'required|exists:users,id',
            'major_id' => 'required|exists:majors,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $group = StudyGroup::create($validated);
        return response()->json($group, 201);
    }

    public function update(Request $request, $id)
    {
        $group = StudyGroup::findOrFail($id);

        $validated = $request->validate([
            'group_name' => 'sometimes|required|string|max:255',
            'major' => 'sometimes|required|string',
            'course_code' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'members' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|required_if:is_online,false',
            'is_online' => 'sometimes|required|boolean',
            'is_complete' => 'sometimes|boolean',
            'meeting_time' => 'sometimes|required|date',
            'leader_id' => 'sometimes|required|exists:users,id',
            'major_id' => 'sometimes|required|exists:majors,id',
            'course_id' => 'sometimes|required|exists:courses,id',
        ]);

        $group->update($validated);
        return response()->json($group);
    }

    public function destroy($id)
    {
        $group = StudyGroup::findOrFail($id);
        $group->delete();

        return response()->json(['message' => 'Study group deleted.']);
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');

        $results = StudyGroup::where('group_name', 'like', "%$keyword%")
            ->orWhere('major', 'like', "%$keyword%")
            ->orWhere('course_code', 'like', "%$keyword%")
            ->orWhereHas('course', function ($query) use ($keyword) {
                $query->where('name', 'like', "%$keyword%");
            })
            ->with(['leader', 'major', 'course']) // Include relationships
            ->get();

        return response()->json($results);
    }

    // Join study group
    public function joinGroup($groupId)
    {
        $user = Auth::user();
        $group = StudyGroup::findOrFail($groupId);

        if ($group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already a member of this group'], 400);
        }

        if ($group->capacity !== null && $group->members()->count() >= $group->capacity) {
            return response()->json(['message' => 'Group is full'], 400);
        }

        $group->members()->attach($user->id);
        $group->increment('members'); // Update members count if you want to keep it in the table

        return response()->json(['message' => 'Joined group successfully']);
    }

    // Leave study group
    public function leaveGroup($groupId)
    {
        $user = Auth::user();
        $group = StudyGroup::findOrFail($groupId);

        if (! $group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this group'], 400);
        }

        $group->members()->detach($user->id);
        $group->decrement('members'); // Update members count

        return response()->json(['message' => 'Left group successfully']);
    }
}