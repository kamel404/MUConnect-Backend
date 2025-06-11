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
    public function index()
    {
        return Event::latest()->get();
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
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
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
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
    public function destroy(Event $event)
    {
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }   
        $event->delete();
        return response()->json([
            'message' => 'Event deleted successfully'
        ], 204);
    }
}
