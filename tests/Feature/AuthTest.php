<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_logout_with_sanctum_cookie_auth()
    {
        // Create user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Login (simulate browser with cookie)
        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(204); // Sanctum login usually returns 204 No Content

        // Hit protected route using the same session (acting as the user)
        $userResponse = $this->getJson('/api/user');

        $userResponse->assertOk();
        $userResponse->assertJson([
            'email' => 'test@example.com',
        ]);

        // Logout
        $logoutResponse = $this->postJson('/logout');
        $logoutResponse->assertStatus(204); // Logout usually returns 204 as well

        // Try again after logout (should be unauthenticated)
        $afterLogout = $this->getJson('/api/user');
        $afterLogout->assertUnauthorized();
    }
}
