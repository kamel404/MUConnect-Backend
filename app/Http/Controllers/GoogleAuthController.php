<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback()
    {
        try {
            $user = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    
        $existingUser = User::where('email', $user->email)->first();
    
        if ($existingUser) {
            // Log in the user
            Auth::login($existingUser);
            
            // Create a Sanctum token for API access
            $token = $existingUser->createToken('google-auth-token')->plainTextToken;
            
            // Redirect to frontend with user data and token
            $userData = json_encode([
                'message' => 'User authenticated successfully',
                'user' => Auth::user(),
                'token' => $token
            ]);
            return redirect('http://localhost:5173/google-callback?data=' . urlencode($userData));
        } else {
            $tempToken = Str::random(40);
            Cache::put('google_auth_' . $tempToken, [
                'email' => $user->email,
                'name' => $user->name,
                'google_id' => $user->id,
            ], now()->addMinutes(30));
            
            // Redirect to frontend with new user data
            $newUserData = json_encode([
                'message' => 'Additional information required',
                'is_new_user' => true,
                'temp_token' => $tempToken,
                'email' => $user->email,
                'name' => $user->name
            ]);
            return redirect('http://localhost:5173/google-callback?data=' . urlencode($newUserData));
        }
    }
    
    /**
     * Complete user registration with faculty and major information
     */
    public function completeRegistration(Request $request)
    {
        $validated = $request->validate([
            'temp_token' => 'required|string',
            'faculty_id' => 'required|exists:faculties,id',
            'major_id' => 'required|exists:majors,id',
            'username' => 'required|unique:users,username'
        ]);
        
        // Retrieve the temporary stored Google data
        $googleData = Cache::get('google_auth_' . $validated['temp_token']);
        
        if (!$googleData) {
            return response()->json([
                'message' => 'Registration session expired or invalid',
            ], 400);
        }
        
        // Create the new user with all needed information
        $newUser = User::create([
            'email' => $googleData['email'],
            'first_name' => explode(' ', $googleData['name'])[0] ?? '',
            'last_name' => explode(' ', $googleData['name'])[1] ?? '',
            'username' => $validated['username'],
            'faculty_id' => $validated['faculty_id'],
            'major_id' => $validated['major_id'],
            'password' => bcrypt(Str::random(16)), // Set a random password
            'email_verified_at' => now(),
            'is_active' => true
        ]);
        
        // Assign default student role
        $newUser->assignRole('student');
        
        // Remove the temporary token from cache
        Cache::forget('google_auth_' . $validated['temp_token']);
        
        // Log in the user
        Auth::login($newUser);
        
        // Create a Sanctum token for API access
        $token = $newUser->createToken('google-auth-token')->plainTextToken;
        
        return response()->json([
            'message' => 'Registration completed successfully',
            'user' => Auth::user(),
            'token' => $token
        ]);
    }
    
    /**
     * Logout a user who signed in with Google
     * Revokes their current token and logs them out
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        
        // Logout from the session
        Auth::logout();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
