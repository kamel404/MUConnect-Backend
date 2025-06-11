<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 8);
    
        $events = Event::latest()->paginate($perPage);
    
        return response()->json($events);
    }
    

    public function myEvents(Request $request)
    {
        $user = $request->user();

        return Event::where('user_id', $user->id)->latest()->get();
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
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
            'category' => 'sometimes|required|string|max:255',
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
