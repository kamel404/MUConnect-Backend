<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\Notification;

class AuthController extends Controller
{
    /**
     * Register a new user and return a token
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'username'   => 'required|unique:users,username',
            'email' => [
                'required',
                'email',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with($value, '@mu.edu.lb')) {
                        $fail('The email must be an email address with the domain @mu.edu.lb.');
                    }
                },
            ],

            'password' => 'required|string|min:8|confirmed',

            // Add faculty and major validation
            'faculty_id' => 'required|exists:faculties,id',
            'major_id' => 'required|exists:majors,id',

            // Optional: Add validation to ensure major belongs to selected faculty
            'major_id' => [
                'required',
                'exists:majors,id',
                function ($attribute, $value, $fail) use ($request) {
                    $major = \App\Models\Major::find($value);
                    if ($major && $major->faculty_id != $request->faculty_id) {
                        $fail('The selected major does not belong to the selected faculty.');
                    }
                }
            ],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'faculty_id' => $validated['faculty_id'],
            'major_id' => $validated['major_id'],
            'is_active' => true,
        ]);

        // Assign default student role
        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $user->assignRole($studentRole);
        }

        // Create token with abilities based on role
        $token = $user->createToken('auth_token', $user->getRoleNames())->plainTextToken;

        // Send welcome notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'sender_id' => null,
            'type' => 'welcome',
            'data' => [
                'message' => 'Welcome to the community, ' . $user->first_name . '!'
            ],
        ]);
        logger($notification);

        // Load relationships for response
        $user->load(['faculty', 'major', 'roles']);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Login user and return a token
     */
    public function login(Request $request)
    {
        // Validate that the login identifier and password are provided
        $request->validate([
            'login'    => 'required',
            'password' => 'required',
        ]);

        // Retrieve the login input and determine if it's an email or username
        $loginInput = $request->input('login');
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Build credentials array
        $credentials = [
            $loginField => $loginInput,
            'password'  => $request->input('password')
        ];

        // Attempt to authenticate
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where($loginField, $loginInput)->firstOrFail();

        // No token deletion here, so multiple tokens can exist per user
        $token = $user->createToken('auth_token', $user->getRoleNames())->plainTextToken;

        return response()->json([
            'message' => 'Login Successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }


    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        // Revoke only the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
