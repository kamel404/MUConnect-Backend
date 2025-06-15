<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SectionRequest;
use App\Models\Application;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SectionRequestController extends Controller
{
    // List all requests (feed) except the current user's
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = SectionRequest::with(['requester', 'applications'])
            ->withCount('applications')
            ->where('requester_id', '!=', $userId);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            // Default to pending if not specified
            $query->where('status', 'pending');
        }
        // Only filter by course_name, current_day, desired_day
        foreach ([
            'course_name', 'current_day', 'desired_day'
        ] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        // No general search

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'applications_count'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);
        $perPage = $request->input('per_page', 6);
        return $query->paginate($perPage);
    }

    // List current user's own requests (history)
    public function myRequests(Request $request)
    {
        $userId = $request->user()->id;
        $query = SectionRequest::with(['applications.user'])
            ->withCount('applications')
            ->where('requester_id', $userId);
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        foreach ([
            'course_name', 'current_section', 'desired_section',
            'current_day', 'desired_day', 'current_time', 'desired_time'
        ] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        // General search
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('course_name', 'like', "%$q%")
                    ->orWhere('current_section', 'like', "%$q%")
                    ->orWhere('desired_section', 'like', "%$q%")
                    ->orWhere('reason', 'like', "%$q%") ;
            });
        }
        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'applications_count'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);
        $perPage = $request->input('per_page', 6);
        return $query->paginate($perPage);
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
        $this->authorize('update', $sectionRequest);
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
        $oldStatus = $sectionRequest->status;
        $sectionRequest->update($data);
        return $sectionRequest;
    }

    // Delete a request (only by owner)
    public function destroy(Request $request, $id)
    {
        $sectionRequest = SectionRequest::findOrFail($id);
        $this->authorize('delete', $sectionRequest);
        $sectionRequest->delete();
        return response()->json(['message' => 'Request deleted']);
    }
}
