<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\Event;

class OverviewController extends Controller
{
    /**
     * Return an aggregated overview of the authenticated user to power the dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load(['roles', 'faculty', 'major']);

        $overview = [
            'study_groups' => [
                'total'   => $user->studyGroups()->count(),
                'leading' => $user->ledStudyGroups()->count(),
            ],
            'events' => [
                'registered' => $user->registeredEvents()->count(),
                'upcoming'   => $user->registeredEvents()->where('event_datetime', '>', now())->count(),
            ],
            'resources' => [
                'shared' => $user->resources()->count(),
                'saved'  => $user->savedItems()->count(),
            ],
            'clubs' => [
                'total' => Club::count(),
            ]
        ];

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'username'   => $user->username,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'avatar'     => $user->avatar_url,
                'roles'      => $user->roles->pluck('name'),
                'faculty'    => $user->faculty,
                'major'      => $user->major,
            ],
            'overview' => $overview,
            'general' => [
                'latest_resources' => Resource::with(['user'])
                    ->latest()
                    ->take(5)
                    ->get(),
                'upcoming_events' => Event::where('event_datetime', '>', now())
                    ->orderBy('event_datetime')
                    ->take(5)
                    ->get(),
            ],
        ]);
    }
}
