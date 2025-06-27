<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Http\Controllers\Controller;
use App\Models\SectionRequest;

class ApplicationController extends Controller
{

 // List all applications for a request (for the owner)
    public function forRequest(Request $request, $requestId)
    {
        $sectionRequest = SectionRequest::findOrFail($requestId);
        if ($sectionRequest->requester_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return Application::with('user')->where('request_id', $requestId)->get();
    }

    // Show a single application
    public function show($id)
    {
        return Application::with(['user', 'request'])->findOrFail($id);
    }

    // Apply to a request
    public function store(Request $request, $requestId)
    {
        $userId = $request->user()->id;
        $sectionRequest = SectionRequest::findOrFail($requestId);

        if ($sectionRequest->requester_id == $userId) {
            return response()->json(['message' => 'Cannot apply to your own request'], 400);
        }

        // Only block if there is a still-active application (pending or accepted)
        $existing = Application::where('request_id', $requestId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();
        if ($existing) {
            return response()->json(['message' => 'Already applied'], 400);
        }

        $data = $request->validate([
            'reason' => 'nullable|string'
        ]);

        $application = Application::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null
        ]);
        // Notify the requester
        $sectionRequest->requester->notify(new \App\Notifications\NewApplicationReceived($application));
        return $application;
    }

    // Update an application (approve/decline by owner, or withdraw by applicant)
    public function update(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $sectionRequest = $application->request;
        $userId = $request->user()->id;

        if (!$sectionRequest) {
            return response()->json(['message' => 'Parent request not found'], 404);
        }
        
        $data = $request->validate([
            'status' => 'required|in:pending,accepted,declined,cancelled'
        ]);

        // If owner is updating (approve/decline)
        if ($sectionRequest->requester_id == $userId) {
            if (!in_array($data['status'], ['accepted', 'declined'])) {
                return response()->json(['message' => 'Invalid status change'], 400);
            }
            $application->status = $data['status'];
            $application->save();

            // Notify the applicant
            $application->user->notify(new \App\Notifications\ApplicationStatusChanged($application));

            if ($data['status'] === 'accepted') {
                $sectionRequest->status = 'accepted';
                $sectionRequest->save();
            }
            return $application;
        }

        // If applicant is updating (withdraw/cancel)
        if ($application->user_id == $userId && $data['status'] === 'cancelled') {
            $application->status = 'cancelled';
            $application->save();
            
            return $application;
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Delete an application (by applicant)
    public function destroy(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $application->delete();
        return response()->json(['message' => 'Application deleted']);
    }

    // List applications for a request (for the owner)
    public function myApplications(Request $request)
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 6);
        return Application::with(['user', 'request'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function withdraw(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $userId = $request->user()->id;

        // Only the applicant can withdraw
        if ($application->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update the status to 'cancelled' (or 'withdrawn', if you prefer)
        $application->status = 'cancelled';
        $application->save();

        return response()->json(['message' => 'Application withdrawn', 'application' => $application]);
    }
    
}
