<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SectionRequest;
use App\Models\Application;

class SectionRequestController extends Controller
{
// List all requests (feed) except the current user's
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        return SectionRequest::with(['requester', 'applications'])
            ->where('status', 'pending')
            ->where('requester_id', '!=', $userId)
            ->get();
    }

    // List current user's own requests (history)
    public function myRequests(Request $request)
    {
        $userId = $request->user()->id;
        return SectionRequest::with('applications')
            ->where('requester_id', $userId)
            ->get();
    }

    // Show a single request
    public function show($id)
    {
        return SectionRequest::with(['requester', 'applications'])->findOrFail($id);
    }

    // Create a new request
    public function store(Request $request)
    {
        $data = $request->validate([
            'course_name' => 'required|string',
            'current_section' => 'required|string',
            'desired_section' => 'required|string',
            'current_day' => 'required|string',
            'desired_day' => 'required|string',
            'current_time' => 'required|string',
            'desired_time' => 'required|string',
            'reason' => 'nullable|string'
        ]);
        $data['requester_id'] = $request->user()->id;
        return SectionRequest::create($data);
    }

    // Update a request (only by owner)
    public function update(Request $request, $id)
    {
        $sectionRequest = SectionRequest::findOrFail($id);
        if ($sectionRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $data = $request->validate([
            'course_name' => 'sometimes|string',
            'current_section' => 'sometimes|string',
            'desired_section' => 'sometimes|string',
            'current_day' => 'sometimes|string',
            'desired_day' => 'sometimes|string',
            'current_time' => 'sometimes|string',
            'desired_time' => 'sometimes|string',
            'reason' => 'nullable|string',
            'status' => 'sometimes|in:pending,accepted,declined,cancelled'
        ]);
        $sectionRequest->update($data);
        return $sectionRequest;
    }

    // Delete a request (only by owner)
    public function destroy(Request $request, $id)
    {
        $sectionRequest = SectionRequest::findOrFail($id);
        if ($sectionRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $sectionRequest->delete();
        return response()->json(['message' => 'Request deleted']);
    }
}
