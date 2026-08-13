<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_with_a_bride_role_and_receive_a_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Anna Kowalska',
            'email' => 'anna@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'bride',
            'device_name' => 'iPhone',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'anna@example.com')
            ->assertJsonPath('data.user.roles.0', 'bride');

        $user = User::query()->where('email', 'anna@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('bride'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_invalid_input_and_admin_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'role' => 'admin',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_a_user_can_log_in_and_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.user.id', $user->id)->assertJsonPath('data.token_type', 'Bearer');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_log_out_but_guest_cannot(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized()->assertJsonPath('success', false);

        $user = User::factory()->create();
        $token = $user->createToken('test');

        $this->withToken($token->plainTextToken)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_password_reset_link_is_sent_and_password_can_be_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => Password::createToken($user),
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_email_verification_requires_sanctum_authentication_and_valid_signed_url(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->getJson($url)->assertUnauthorized()->assertJsonPath('success', false);

        Sanctum::actingAs($user);
        $this->getJson($url)->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_authenticated_user_can_delete_own_account_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $this->deleteJson('/api/v1/auth/account', ['password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        Sanctum::actingAs($user);
        $this->deleteJson('/api/v1/auth/account', ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
        $this->deleteJson('/api/v1/auth/account', ['password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
