<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Comment;
use App\Models\Upvote;
use App\Models\Resource;
use App\Models\Course;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        // Validate and sanitize inputs
        $validated = $request->validate([
            'search' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9\s\-_.@]+$/',
            'per_page' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'faculty_id' => 'nullable|integer|exists:faculties,id',
            'major_id' => 'nullable|integer|exists:majors,id'
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $searchTerm = $validated['search'] ?? null;

        $query = User::select([
            'id', 'username', 'first_name', 'last_name',
            'email', 'is_active', 'faculty_id', 'major_id'
        ])->with(['faculty:id,name', 'major:id,name']);

        // Apply search filter safely using parameter binding
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('username', 'like', '%' . $searchTerm . '%')
                ->orWhere('first_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply additional filters
        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        if (isset($validated['faculty_id'])) {
            $query->where('faculty_id', $validated['faculty_id']);
        }

        if (isset($validated['major_id'])) {
            $query->where('major_id', $validated['major_id']);
        }

        try {
            $users = $query->paginate($perPage);
            return response()->json($users);
        } catch (\Exception $e) {
            \Log::error('User index failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve users',
                'errors' => ['general' => ['An error occurred while fetching users']]
            ], 500);
        }
    }

    /**
     * Toggle user's active status (admin only)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message'   => 'User status updated successfully',
            'is_active' => $user->is_active,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles', 'faculty', 'major');
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'), // array of role names
            'faculty' => $user->faculty,
            'major' => $user->major,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'is_admin' => $user->hasRole('admin'),
            'is_moderator' => $user->hasRole('moderator'),
            'is_student' => $user->hasRole('student'),
        ]);
    }

    /**
     * Show user profile
     */
    // return with major name and faculty name
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'), // array of role names
            'faculty' => $user->faculty,
            'major' => $user->major,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'is_admin' => $user->hasRole('admin'),
            'is_moderator' => $user->hasRole('moderator'),
            'is_student' => $user->hasRole('student'),
        ]);
    }

    /**
     * Update profile
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username'   => 'sometimes|required|unique:users,username,' . $id,
            'first_name' => 'sometimes|required|string',
            'last_name'  => 'sometimes|required|string',
            'email'      => [
                'sometimes',
                'unique:users,email,' . $id,
                'email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with($value, '@mu.edu.lb')) {
                        $fail('The ' . $attribute . ' must be an email address with the domain @mu.edu.lb.');
                    }
                },
            ],
            'password'   => 'sometimes|required|min:6',
            'avatar'     => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio'        => 'sometimes|nullable|string',
        ]);

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            // Store the uploaded file
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            // Get just the filename without the path
            $avatarFileName = basename($avatarPath);
            $validated['avatar'] = $avatarFileName;
        }

        // Remove fields that should be handled separately
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Delete profile
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    // get user role
    // not working, change it
    public function getUserRole($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $roles = $user->getRoleNames();

        return response()->json(['roles' => $roles]);
    }

    // update user role
    public function updateUserRole(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json(['message' => 'User role updated successfully']);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'   => 'required|unique:users,username',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => [
                'required',
                'unique:users,email',
                'email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with($value, '@mu.edu.lb')) {
                        $fail('The ' . $attribute . ' must be an email address with the domain @mu.edu.lb.');
                    }
                },
            ],
            'password'   => 'required|min:6',
            'avatar'     => 'nullable|string',
            'bio'        => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * Get user profile with analytics
     */
    public function profile($id = null)
    {
        // If no ID provided, use authenticated user
        $userId = $id ?? auth()->id();
        $user = User::with(['faculty', 'major', 'roles', 'studyGroups', 'registeredEvents'])
            ->findOrFail($userId);

        // Additional individual contribution stats
        $commentsMade = Comment::where('user_id', $user->id)->count();
        $upvotesGiven = Upvote::where('user_id', $user->id)->count();

        // Calculate upvotes received (on user profile, resources, and comments)
        $upvotesReceivedUser = Upvote::where([
            'upvoteable_type' => User::class,
            'upvoteable_id'   => $user->id,
        ])->count();

        $resourceIds = $user->resources()->pluck('id');
        $upvotesReceivedResources = $resourceIds->isNotEmpty()
            ? Upvote::where('upvoteable_type', Resource::class)
                ->whereIn('upvoteable_id', $resourceIds)
                ->count()
            : 0;

        $commentIds = Comment::where('user_id', $user->id)->pluck('id');
        $upvotesReceivedComments = $commentIds->isNotEmpty()
            ? Upvote::where('upvoteable_type', Comment::class)
                ->whereIn('upvoteable_id', $commentIds)
                ->count()
            : 0;

        $upvotesReceived = $upvotesReceivedUser + $upvotesReceivedResources + $upvotesReceivedComments;

        // Global stats for charts (top 5) - only for admins/moderators
        $charts = null;
        $currentUser = auth()->user();
        
        if ($currentUser && ($currentUser->hasRole('admin') || $currentUser->hasRole('moderator'))) {
            $topPostingUsers = User::withCount('resources')
                ->orderBy('resources_count', 'desc')
                ->take(5)
                ->get(['id', 'username', 'resources_count']);

            $topCommentingUsers = Comment::select('user_id', DB::raw('COUNT(*) as comments_count'))
                ->groupBy('user_id')
                ->orderBy('comments_count', 'desc')
                ->take(5)
                ->get()
                ->map(function ($row) {
                    $usr = User::find($row->user_id);
                    return [
                        'id'             => $usr->id,
                        'username'       => $usr->username,
                        'comments_count' => $row->comments_count,
                    ];
                });

            $topUpvotingUsers = Upvote::select('user_id', DB::raw('COUNT(*) as upvotes_given'))
                ->groupBy('user_id')
                ->orderBy('upvotes_given', 'desc')
                ->take(5)
                ->get()
                ->map(function ($row) {
                    $usr = User::find($row->user_id);
                    return [
                        'id'            => $usr->id,
                        'username'      => $usr->username,
                        'upvotes_given' => $row->upvotes_given,
                    ];
                });

            $topCourses = \App\Models\Course::withCount('resources')
                ->orderBy('resources_count', 'desc')
                ->take(5)
                ->get(['id', 'code', 'title', 'resources_count']);

            $charts = [
                'top_posting_users'    => $topPostingUsers,
                'top_commenting_users' => $topCommentingUsers,
                'top_upvoting_users'   => $topUpvotingUsers,
                'top_courses'          => $topCourses,
            ];
        }

        // Gather analytics data (without section requests and clubs)
        $analytics = [
            'study_groups' => [
                'total' => $user->studyGroups()->count(),
                'leading' => $user->ledStudyGroups()->count(),
            ],
            'events' => [
                'created' => $user->events()->count(),
                'registered' => $user->registeredEvents()->count(),
                'upcoming' => $user->registeredEvents()->where('event_datetime', '>', now())->count(),
            ],
            // Remove clubs section since users don't join clubs anymore
            'resources' => [
                'shared' => $user->resources()->count(),
            ],
            'contributions' => [
                'comments_made'   => $commentsMade,
                'upvotes_given'   => $upvotesGiven,
                'upvotes_received'=> $upvotesReceived,
            ],
            'applications' => [
                'total' => $user->applications()->count(),
                'pending' => $user->applications()->where('status', 'pending')->count(),
                'accepted' => $user->applications()->where('status', 'accepted')->count(),
            ],
            // Modify activity to exclude section requests
            'activity' => $this->getRecentActivityWithoutSectionRequests($user),
        ];

        // Add charts only if user is admin/moderator
        if ($charts !== null) {
            $analytics['charts'] = $charts;
        }

        return response()->json([
            'user' => $user,
            'analytics' => $analytics
        ]);
    }

    /**
     * Get recent user activity without section requests
     */
    private function getRecentActivityWithoutSectionRequests($user)
    {
        // Combine recent activity from different sources
        $activity = collect();

        // Add recent study group joins
        $user->studyGroups()
            ->withPivot('created_at')
            ->orderBy('pivot_created_at', 'desc')
            ->take(5)
            ->get()
            ->each(function($group) use (&$activity) {
                $activity->push([
                    'type' => 'study_group_join',
                    'date' => $group->pivot->created_at,
                    'data' => [
                        'group_id' => $group->id,
                        'group_name' => $group->name
                    ]
                ]);
            });

        // Add recent event registrations
        $user->registeredEvents()
            ->withPivot('created_at')
            ->orderBy('pivot_created_at', 'desc')
            ->take(5)
            ->get()
            ->each(function($event) use (&$activity) {
                $activity->push([
                    'type' => 'event_registration',
                    'date' => $event->pivot->created_at,
                    'data' => [
                        'event_id' => $event->id,
                        'event_name' => $event->name
                    ]
                ]);
            });

        // Sort combined activity by date
        return $activity->sortByDesc('date')->values()->all();
    }

    /**
     * Get recent activity for the authenticated user
     */
    public function recentActivity()
    {
        $user = Auth::user();
        $activity = $this->getRecentActivity($user);

        return response()->json(['activity' => $activity]);
    }

}
