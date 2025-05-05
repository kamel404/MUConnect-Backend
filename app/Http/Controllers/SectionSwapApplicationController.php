<?php

namespace App\Http\Controllers;
use App\Models\SectionSwapApplication;
use Illuminate\Http\Request;

class SectionSwapApplicationController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'request_id' => 'required|exists:exchange_requests,id',
            'message' => 'nullable|string',
        ]);

        return SectionSwapApplication::create([
            'request_id' => $validated['request_id'],
            'applicant_id' => auth()->id(),
            'message' => $validated['message'],
        ]);
    }
}
