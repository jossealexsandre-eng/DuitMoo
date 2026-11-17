<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_gets_default_role_and_categories(): void
    {
        $response = $this->post('/register', [
            'name' => 'Fajar Pratama',
            'email' => 'fajar@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // Attempt privilege escalation via payload
        ]);

        $response->assertRedirect('/onboarding');

        $this->assertDatabaseHas('users', [
            'email' => 'fajar@example.com',
            'role' => 'user', // Must remain 'user'
            'is_active' => true,
        ]);

        $user = User::where('email', 'fajar@example.com')->first();
        $this->assertNotNull($user->preferences);
        $this->assertEquals(60, $user->preferences->default_needs_percent);
        $this->assertEquals(30, $user->preferences->default_wants_percent);
        $this->assertEquals(10, $user->preferences->default_emergency_percent);

        // Ensure default categories are seeded
        $this->assertGreaterThan(0, $user->categories()->count());
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'andi@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'andi@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_is_blocked_from_login(): void
    {
        $user = User::factory()->create([
            'email' => 'banned@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'banned@example.com',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
