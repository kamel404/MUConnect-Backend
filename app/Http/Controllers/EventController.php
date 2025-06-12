<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EventRegistration;

class EventController extends Controller
{
    // Save an event for the authenticated user
    public function save($id)
    {
        $user = auth()->user();
        $event = \App\Models\Event::findOrFail($id);

        $saved = \App\Models\SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'saveable_id' => $event->id,
            'saveable_type' => \App\Models\Event::class,
        ]);

        return response()->json(['saved' => true, 'item' => $saved], 201);
    }

    // Unsave an event for the authenticated user
    public function unsave($id)
    {
        $user = auth()->user();
        $event = \App\Models\Event::findOrFail($id);

        $deleted = \App\Models\SavedItem::where([
            'user_id' => $user->id,
            'saveable_id' => $event->id,
            'saveable_type' => \App\Models\Event::class,
        ])->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 8);
        $query = Event::query();

        // Handle time_filter (today, this_week, this_month)
        if ($request->filled('time_filter') && !$request->filled('start_date') && !$request->filled('end_date')) {
            $now = now();
            switch ($request->time_filter) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                case 'this_week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
                case 'this_month':
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
                default:
                    $start = null;
                    $end = null;
            }
            if ($start && $end) {
                $query->whereBetween('event_datetime', [$start, $end]);
            }
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('event_datetime', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('event_datetime', '<=', $request->end_date);
        }

        $events = $query->latest()->paginate($perPage);
        return response()->json($events);
    }
    
    public function myRegisteredEvents(Request $request)
    {
        $user = $request->user();
        $query = Event::whereHas('registrations', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Handle time_filter (today, this_week, this_month)
        if ($request->filled('time_filter') && !$request->filled('start_date') && !$request->filled('end_date')) {
            $now = now();
            switch ($request->time_filter) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                case 'this_week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
                case 'this_month':
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
                default:
                    $start = null;
                    $end = null;
            }
            if ($start && $end) {
                $query->whereBetween('event_datetime', [$start, $end]);
            }
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('event_datetime', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('event_datetime', '<=', $request->end_date);
        }

        $perPage = $request->get('per_page', 10);
        $events = $query->latest()->paginate($perPage);
        return response()->json($events);
    }
    



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'event_datetime' => 'required|date',
            'location' => 'required|string|max:255',
            'organizer' => 'required|string|max:255',
            'description' => 'nullable|string',
            'speaker_names' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('events', 'public');
        }

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        return response()->json($event);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'event_datetime' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'organizer' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'speaker_names' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('events', 'public');
        }

        $event->update($validated);

        return response()->json($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Event $event)
    {
    
        $event->delete();
    
        return response()->json(['message' => 'Event deleted successfully'], 200);
    }

    public function register(Request $request, Event $event)
    {
        $user = $request->user();

        $alreadyRegistered = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json(['message' => 'Already registered'], 400);
        }

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $event->increment('attendees_count');

        return response()->json(['message' => 'Registered successfully']);
    }

    public function unregister(Request $request, Event $event)
    {
        $user = $request->user();

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

    if (!$registration) {
        return response()->json(['message' => 'Not registered'], 400);
    }

    $registration->delete();
    $event->decrement('attendees_count');

    return response()->json(['message' => 'Unregistered successfully']);
}


    
}
