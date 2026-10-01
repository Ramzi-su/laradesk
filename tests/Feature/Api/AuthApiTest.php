<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->agent()->create(['email' => 'agent@laradesk.test']);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'agent@laradesk.test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'role']])
            ->assertJsonPath('user.role', 'agent')
            ->assertJsonMissingPath('user.email');

        $this->assertSame('phpunit', $user->tokens()->sole()->name);
        $this->getJson(route('api.v1.tickets.index'), ['Authorization' => 'Bearer '.$response->json('token')])
            ->assertOk();
    }

    public function test_login_fails_with_the_same_message_for_unknown_email_and_wrong_password(): void
    {
        User::factory()->create(['email' => 'known@laradesk.test']);

        $wrongPassword = $this->postJson(route('api.v1.auth.login'), ['email' => 'known@laradesk.test', 'password' => 'nope']);
        $unknownEmail = $this->postJson(route('api.v1.auth.login'), ['email' => 'unknown@laradesk.test', 'password' => 'nope']);

        $wrongPassword->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'victim@laradesk.test']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('api.v1.auth.login'), ['email' => 'victim@laradesk.test', 'password' => 'guess'.$attempt])
                ->assertUnprocessable();
        }

        $this->postJson(route('api.v1.auth.login'), ['email' => 'victim@laradesk.test', 'password' => 'password'])
            ->assertTooManyRequests();
    }

    public function test_requests_without_token_get_a_json_401(): void
    {
        // No "Accept: application/json" header: the API must still answer in JSON, not redirect to /login.
        $this->get('/api/v1/tickets')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_logout_revokes_the_current_token_only(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('laptop')->plainTextToken;
        $user->createToken('phone');

        $this->postJson(route('api.v1.auth.logout'), [], ['Authorization' => "Bearer {$current}"])
            ->assertNoContent();

        $this->assertSame(['phone'], $user->tokens()->pluck('name')->all());
        $this->assertNull(PersonalAccessToken::findToken($current));
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = User::factory()->create()->createToken('old')->plainTextToken;

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->getJson(route('api.v1.tickets.index'), ['Authorization' => "Bearer {$token}"])
            ->assertUnauthorized();
    }
}
