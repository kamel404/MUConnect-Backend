<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Club;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\ClubMember;

class ClubController extends Controller
{

    /**
     * Display a listing of clubs
     */
    public function index(Request $request)
    {
        $query = $request->input('query');

        $clubs = Club::when($query, function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%');
        })
            ->paginate(10);

        return response()->json($clubs);
    }

    /**
     * Display a specific club
     */
    public function show($id)
    {
        $club = Club::with('clubMembers')->findOrFail($id);
        return response()->json($club);
    }

    /**
     * Create a new club (only moderators or admins)
     */
    public function store(Request $request)
    {
        // Check if user has permission to create clubs
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $validated['members'] = 0; // Initialize members count
        $club = Club::create($validated);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $club->logo = $request->file('logo')->store('clubs', 'public');
            $club->save();
        }

        return response()->json($club, 201);
    }

    /**
     * Display events for a specific club
     */
    public function clubEvents($clubId)
    {
        $club = Club::findOrFail($clubId);
        $events = $club->events()->orderBy('event_datetime', 'desc')->paginate(10);

        return response()->json([
            'club' => $club,
            'events' => $events
        ]);
    }

    /**
     * Create an event for a specific club
     */
    public function createClubEvent(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        // Check if user has permission to create events for this club
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_datetime' => 'required|date',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'speaker_names' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['club_id'] = $club->id;
        $validated['is_club_event'] = true;
        $validated['organizer'] = $club->name;

        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('events', 'public');
        }

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    /**
     * Update an existing club (only moderators or admins)
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $club = Club::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('clubs', 'public');
        }

        $club->update($validated);

        return response()->json($club);
    }

    /**
     * Delete a club along with its events (only moderators or admins)
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $club = Club::findOrFail($id);

        // Delete related events
        $club->events()->delete();

        // Delete all club members
        $club->clubMembers()->delete();

        $club->delete();

        return response()->json(['message' => 'Club and its related events deleted successfully.']);
    }

    /**
     * Add a member to club with picture and name
     */
    public function addMember(Request $request, $clubId)
    {
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $picturePath = null;
        if ($request->hasFile('picture')) {
            $picturePath = $request->file('picture')->store('club_members', 'public');
        }

        $member = $club->clubMembers()->create([
            'name' => $validated['name'],
            'picture' => $picturePath,
        ]);

        $club->increment('members');

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $member
        ], 201);
    }

    /**
     * Update a club member's info (name/picture)
     */
    public function updateMember(Request $request, $clubId, $memberId)
    {
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $club = Club::findOrFail($clubId);

        $clubMember = ClubMember::where('club_id', $clubId)
            ->where('id', $memberId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('picture')) {
            $validated['picture'] = $request->file('picture')->store('club_members', 'public');
        }

        $clubMember->update($validated);

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => $clubMember
        ]);
    }

    /**
     * Remove a member from club
     */
    public function removeMember($clubId, $memberId)
    {
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $club = Club::findOrFail($clubId);

        $clubMember = ClubMember::where('club_id', $clubId)
            ->where('id', $memberId)
            ->firstOrFail();

        $clubMember->delete();

        $club->decrement('members');

        return response()->json(['message' => 'Member removed successfully']);
    }

    /**
     * Get all club members with their info
     */
    public function getClubMembers($clubId)
    {
        $club = Club::findOrFail($clubId);
        $members = $club->clubMembers()->get();

        return response()->json([
            'club' => $club,
            'members' => $members
        ]);
    }
}
