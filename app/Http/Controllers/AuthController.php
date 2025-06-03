<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class AuthController extends Controller
{
    /**
     * Register a new user
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
        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        // On successful authentication, create a token for the user
        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message' => 'Login Successful ', 'token' => $token]);
    }
    

    /**
     * Logout user
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
