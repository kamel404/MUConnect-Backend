<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'required|unique:users,username',
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
        ]);

        $user = User::create($validated);
        // Assign default role
        $user->assignRole('student');
        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    /**
     * Login user by username or email
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],

            ]);
        }
        $request->session()->regenerate(); // Regenerate session to prevent session fixation attacks
        return response()->json([
            'message' => 'Logged in successfully',
            'user' => Auth::user()

        ]);
    }


    /**
     * Logout user
     */
    
    public function logout(Request $request)
    
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();// Regenerate CSRF token

        return response()->json(['message' => 'Logged out successfully']);
    }
}
