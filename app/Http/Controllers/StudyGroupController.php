<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyGroupController extends Controller
{
    // Save a study group for the authenticated user
    public function save($id)
    {
        $user = auth()->user();
        $group = StudyGroup::findOrFail($id);

        $saved = \App\Models\SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'saveable_id' => $group->id,
            'saveable_type' => StudyGroup::class,
        ]);

        return response()->json(['saved' => true, 'item' => $saved], 201);
    }

    // Unsave a study group for the authenticated user
    public function unsave($id)
    {
        $user = auth()->user();
        $group = StudyGroup::findOrFail($id);

        $deleted = \App\Models\SavedItem::where([
            'user_id' => $user->id,
            'saveable_id' => $group->id,
            'saveable_type' => StudyGroup::class,
        ])->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    public function index(Request $request)
    {
        $query = StudyGroup::with(['creator', 'major', 'course', 'faculty'])
            ->withCount('members');

        // Search by name or description if 'q' is present
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->has('is_online')) {
            $query->where('is_online', $request->boolean('is_online'));
        }
        if ($request->has('is_complete')) {
            $query->where('is_complete', $request->boolean('is_complete'));
        }
        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->has('major_id')) {
            $query->where('major_id', $request->major_id);
        }
        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        $perPage = $request->input('per_page', 4);
        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        $group = StudyGroup::with(['creator', 'major', 'course', 'faculty', 'members'])
            ->findOrFail($id);
        return response()->json($group);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:2',
            'location' => 'nullable|string|required_if:is_online,false',
            'is_online' => 'boolean',
            'meeting_time' => 'required|date',
            'course_id' => 'required|exists:courses,id',
            'major_id' => 'required|exists:majors,id',
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        // Set creator to current user
        $validated['creator_id'] = Auth::id();
        $validated['is_complete'] = false;

        // Create the study group
        $group = StudyGroup::create($validated);

        // Add creator as member and admin
        $group->members()->attach(Auth::id(), ['is_admin' => true]);

        return response()->json($group->load('creator', 'course', 'major', 'faculty'), 201);
    }

    public function update(Request $request, $id)
    {
        $group = StudyGroup::findOrFail($id);

        // Check if user is admin of the group
        if (!$group->admins->contains(Auth::id())) {
            return response()->json(['message' => 'Unauthorized. Only group admins can update the group.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:2',
            'location' => 'nullable|string|required_if:is_online,false',
            'is_online' => 'boolean',
            'is_complete' => 'boolean',
            'meeting_time' => 'sometimes|required|date',
            'course_id' => 'sometimes|required|exists:courses,id',
            'major_id' => 'sometimes|required|exists:majors,id',
            'faculty_id' => 'sometimes|required|exists:faculties,id',
        ]);

        $group->update($validated);
        return response()->json($group->load('creator', 'course', 'major', 'faculty'));
    }

    public function destroy($id)
    {
        $group = StudyGroup::findOrFail($id);

        // Check if user is the creator
        if ($group->creator_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized. Only the creator can delete the group.'], 403);
        }

        $group->delete();
        return response()->json(['message' => 'Study group deleted.']);
    }

    // Join study group
    public function joinGroup($groupId)
    {
        $user = Auth::user();
        $group = StudyGroup::findOrFail($groupId);

        // Check if already a member
        if ($group->members->contains($user->id)) {
            return response()->json(['message' => 'You are already a member of this group.'], 422);
        }

        // Check if group is full
        if ($group->isFull()) {
            return response()->json(['message' => 'This group is full.'], 422);
        }

        // Check if group is complete
        if ($group->is_complete) {
            return response()->json(['message' => 'This group is marked as complete and not accepting new members.'], 422);
        }

        // Add user to group
        $group->members()->attach($user->id);

        return response()->json(['message' => 'Successfully joined the study group.']);
    }

    // Leave study group
    public function leaveGroup($groupId)
    {
        $user = Auth::user();
        $group = StudyGroup::findOrFail($groupId);

        // Check if a member
        if (!$group->members->contains($user->id)) {
            return response()->json(['message' => 'You are not a member of this group.'], 422);
        }

        // If the creator (admin) is leaving, delete the group and remove all members
        if ($group->creator_id === $user->id) {
            $group->delete();
            return response()->json(['message' => 'You were the creator/admin. The group has been deleted and all members removed.']);
        }

        // Remove user from group
        $group->members()->detach($user->id);
        return response()->json(['message' => 'Successfully left the study group.']);
    }

    // Make a member an admin
    public function makeAdmin(Request $request, $groupId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = StudyGroup::findOrFail($groupId);

        // Check if current user is admin
        if (!$group->admins->contains(Auth::id())) {
            return response()->json(['message' => 'Unauthorized. Only admins can promote members.'], 403);
        }

        // Check if target user is a member
        if (!$group->members->contains($validated['user_id'])) {
            return response()->json(['message' => 'User is not a member of this group.'], 422);
        }

        // Update pivot to make the user an admin
        $group->members()->updateExistingPivot($validated['user_id'], ['is_admin' => true]);

        return response()->json(['message' => 'User has been promoted to admin.']);
    }

    // Get all study groups where the authenticated user is a member
    // return members count with it
    public function myGroups(Request $request)
    {
        $user = $request->user();
        $groups = $user->studyGroups()->with(['creator', 'major', 'course', 'faculty'])->get();

        // Add member count to each group
        $groups->each(function ($group) {
            $group->member_count = $group->members()->count();
        });

        return response()->json($groups);
    }
}
