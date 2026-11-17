<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_redirects_with_error_when_google_credentials_not_configured(): void
    {
        config(['services.google.client_id' => '']);
        config(['services.google.client_secret' => '']);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
    }

    public function test_redirects_to_google_when_configured(): void
    {
        config(['services.google.client_id' => 'mock-client-id']);
        config(['services.google.client_secret' => 'mock-client-secret']);

        $response = $this->get(route('auth.google'));

        $this->assertStringContainsString('accounts.google.com', (string) $response->headers->get('Location'));
    }

    public function test_can_register_and_login_new_user_with_google_callback(): void
    {
        config(['services.google.client_id' => 'mock-client-id']);
        config(['services.google.client_secret' => 'mock-client-secret']);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-unique-id-12345');
        $abstractUser->shouldReceive('getName')->andReturn('Budi Google');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);
        $abstractUser->shouldReceive('getEmail')->andReturn('budi.google@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/photo.jpg');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $user = User::where('email', 'budi.google@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-unique-id-12345', $user->google_id);
        $this->assertEquals('https://lh3.googleusercontent.com/photo.jpg', $user->avatar);
        $this->assertNotNull($user->preferences);
        $this->assertGreaterThan(0, $user->categories()->count());
        $response->assertRedirect(route('onboarding'));
    }

    public function test_can_login_existing_user_with_google_callback(): void
    {
        config(['services.google.client_id' => 'mock-client-id']);
        config(['services.google.client_secret' => 'mock-client-secret']);

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => 'google-existing-999',
            'is_active' => true,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-existing-999');
        $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_links_existing_email_account_to_google(): void
    {
        config(['services.google.client_id' => 'mock-client-id']);
        config(['services.google.client_secret' => 'mock-client-secret']);

        $user = User::factory()->create([
            'email' => 'regular@example.com',
            'google_id' => null,
            'is_active' => true,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('new-google-id-555');
        $abstractUser->shouldReceive('getEmail')->andReturn('regular@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://google.com/avatar.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertEquals('new-google-id-555', $user->fresh()->google_id);
    }
}
