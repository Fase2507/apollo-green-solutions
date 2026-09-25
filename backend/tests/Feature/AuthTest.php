<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_sends_verification_code(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'message'])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->verification_code);
        $this->assertNotNull($user->verification_code_expires_at);

        Mail::assertSent(VerificationCodeMail::class);
    }

    public function test_verify_with_valid_code_returns_token(): void
    {
        $this->registerUser();

        $code = $this->deliveredCode();

        $this->postJson('/api/verify', [
            'email' => 'john@example.com',
            'verification_code' => $code,
        ])->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_code_expires_at);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $this->registerUser();

        $this->postJson('/api/verify', [
            'email' => 'john@example.com',
            'verification_code' => '000000',
        ])->assertStatus(422);
    }

    public function test_verify_rejects_expired_code(): void
    {
        $this->registerUser();

        User::where('email', 'john@example.com')
            ->update(['verification_code_expires_at' => now()->subMinute()]);

        $this->postJson('/api/verify', [
            'email' => 'john@example.com',
            'verification_code' => $this->deliveredCode(),
        ])->assertStatus(422);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'taken@example.com',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
        ])->assertStatus(422);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secretpass',
        ])->assertStatus(422);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => 'secretpass']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secretpass',
        ])->assertOk()->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);
    }

    public function test_login_with_invalid_credentials_is_rejected(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrongpass',
        ])->assertStatus(422);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['data' => ['email' => $user->email]]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/projects')->assertUnauthorized();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    private function registerUser(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
        ])->assertStatus(201);
    }

    private function deliveredCode(): string
    {
        $mail = Mail::sent(VerificationCodeMail::class)->first();

        preg_match('/\d{6}/', $mail->render(), $matches);

        return $matches[0];
    }
}
