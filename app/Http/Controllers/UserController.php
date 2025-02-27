<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
                'required',
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
        // return with message
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
}
