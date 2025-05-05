<?php

namespace App\Http\Controllers;
use App\Models\SectionSwapRequest;
use Illuminate\Http\Request;

class SectionSwapRequestController extends Controller
{
    public function index() {
        return SectionSwapRequest::with('course', 'student')->where('status', 'open')->get();
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'current_section' => 'required|string',
            'desired_section' => 'required|string',
            'current_day' => 'required|string',
            'current_time' => 'required',
            'desired_day' => 'required|string',
            'desired_time' => 'required',
            'reason' => 'nullable|string',
        ]);

        $validated['student_id'] = auth()->id(); // or $request->user()->id
        return SectionSwapRequest::create($validated);
    }

    public function show($id) {
        return SectionSwapRequest::with('applications.applicant')->findOrFail($id);
    }

    public function acceptApplicant($id, $applicantId) {
        $request = SectionSwapRequest::findOrFail($id);
        $request->update(['status' => 'accepted']);

        // Optionally: mark the accepted application
        return response()->json(['message' => 'Applicant accepted']);
    }
}
