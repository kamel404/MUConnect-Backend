<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('post')->paginate(10);
        return response()->json($events);
    }

    public function show($id)
    {
        // Fetch a single post by ID
        $event = Event::with('post')->findOrFail($id);
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'topic' => 'required|string|max:255',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255' 
        ]);
    
        $event = Event::create($validatedData);
    
        return response()->json(['message' => 'Event created successfully', 'event' => $event], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'topic' => 'sometimes|string|max:255',
            'event_date' => 'sometimes|date',
            'location' => 'sometimes|string|max:255' 
        ]);

        $event = Event::findOrFail($id);
        $event->update($validatedData);

        return response()->json(['message' => 'Event updated successfully', 'event' => $event]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }
}
