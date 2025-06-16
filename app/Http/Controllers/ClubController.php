<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Club;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ClubController extends Controller
{

    /**
     * Display a listing of clubs
     */
    public function index()
    {
        $clubs = Club::paginate(10);
        return response()->json($clubs);
    }
    /**
     * Display a specific club
     */
    public function show($id)
    {
        $club = Club::findOrFail($id);
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
     * Search clubs by name
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $clubs = Club::where('name', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->paginate(10);

        return response()->json($clubs);
    }
    /**
     * Display the members of a specific club
     */
    public function members($clubId)
    {
        $club = Club::findOrFail($clubId);
        $members = $club->members()->paginate(10);

        return response()->json([
            'club' => $club,
            'members' => $members
        ]);
    }
    /**
     * Join a club
     */
    public function joinClub(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $user = Auth::user();

        // Check if user is already a member
        if ($club->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already a member of this club.'], 400);
        }

        // Add user to club members
        $club->members()->attach($user->id);

        // Increment club members count
        $club->increment('members');

        // Send welcome notification with club name
        $user->notify(new \App\Notifications\ClubJoinedNotification($club));

        return response()->json(['message' => 'Successfully joined the club.']);
    }
    /**
     * Leave a club
     */
    public function leaveClub(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $user = Auth::user();

        // Check if user is a member
        if (!$club->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this club.'], 400);
        }

        // Remove user from club members
        $club->members()->detach($user->id);

        // Decrement club members count
        $club->decrement('members');

        return response()->json(['message' => 'Successfully left the club.']);
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

    public function myClubs(Request $request)
    {
        $user = $request->user();
        $clubs = $user->clubs()->paginate(10); // or ->get() for all
        return response()->json($clubs);
    }
}
