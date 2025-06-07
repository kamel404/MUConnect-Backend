<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::paginate(10);
        return response()->json($users);
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
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update profile
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username'   => 'sometimes|required|unique:users,username,'.$id,
            'first_name' => 'sometimes|required|string',
            'last_name'  => 'sometimes|required|string',
            'email'      => [
                'sometimes',
                'unique:users,email,'.$id,
                'email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with($value, '@mu.edu.lb')) {
                        $fail('The '.$attribute.' must be an email address with the domain @mu.edu.lb.');
                    }
                },
            ],
            'password'   => 'sometimes|required|min:6',
            'avatar'     => 'sometimes|nullable|string',
            'bio'        => 'sometimes|nullable|string',
        ]);

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
    public function getUserRole($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $roles = $user->getRoleNames(); // Returns a collection of role names

        return response()->json([
            'user_id' => $user->id,
            'username' => $user->username,
            'roles' => $roles
        ]);
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
                        $fail('The '.$attribute.' must be an email address with the domain @mu.edu.lb.');
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
}
