<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class JwtSessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => str_repeat('test-session-signing-key-', 3)]);
    }

    public function test_current_session_uses_a_digest_and_can_access_user(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $token = JWTAuth::fromUser($user);
        $claims = JWTAuth::setToken($token)->getPayload()->toArray();

        $this->assertSame(hash('sha256', $user->getRememberToken()), $claims['session_version']);
        $this->assertNotContains($user->getRememberToken(), $claims);
        $this->getUser($token)->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_current_session_can_access_user_with_only_the_http_only_cookie(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $token = JWTAuth::fromUser($user);
        $this->clearJwtState();

        $this->call('GET', '/api/user', [], ['b5_auth_token' => $token], [], [
            'HTTP_ACCEPT' => 'application/json',
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_cookie_only_logout_revokes_the_token_for_later_bearer_requests(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $token = JWTAuth::fromUser($user);
        $this->clearJwtState();

        $response = $this->call('POST', '/api/logout', [], ['b5_auth_token' => $token], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('b5_auth_token', $cookies[0]->getName());
        $this->assertLessThanOrEqual(time(), $cookies[0]->getExpiresTime());

        $this->getUser($token)->assertUnauthorized();
    }

    public function test_rotating_the_session_version_rejects_access_and_refresh(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $oldToken = JWTAuth::fromUser($user);
        $this->getUser($oldToken)->assertOk();

        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->getUser($oldToken)->assertUnauthorized();
        $this->clearJwtState();
        $this->postJson('/api/refresh', [], ['Authorization' => 'Bearer '.$oldToken])
            ->assertUnauthorized();
        $this->getUser(JWTAuth::fromUser($user))->assertOk();
    }

    public function test_legacy_session_survives_until_the_first_password_reset(): void
    {
        $user = User::factory()->create(['remember_token' => null]);
        $claims = JWTAuth::setToken(JWTAuth::fromUser($user))->getPayload()->toArray();
        unset($claims['session_version']);
        $legacyToken = app('tymon.jwt.provider.jwt')->encode($claims);

        $this->getUser($legacyToken)->assertOk();

        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->getUser($legacyToken)->assertUnauthorized();
        $this->clearJwtState();
        $this->postJson('/api/refresh', [], ['Authorization' => 'Bearer '.$legacyToken])
            ->assertUnauthorized();
    }

    public function test_malformed_session_claim_is_rejected_without_a_server_error(): void
    {
        $user = User::factory()->create(['remember_token' => null]);
        $claims = JWTAuth::setToken(JWTAuth::fromUser($user))->getPayload()->toArray();
        $claims['session_version'] = ['invalid'];
        $token = app('tymon.jwt.provider.jwt')->encode($claims);

        $this->getUser($token)->assertUnauthorized();
    }

    public function test_refresh_preserves_the_current_session_version_and_access(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $token = JWTAuth::fromUser($user);
        $this->clearJwtState();

        $response = $this->postJson('/api/refresh', [], ['Authorization' => 'Bearer '.$token]);
        $response->assertOk();
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('b5_auth_token', $cookies[0]->getName());
        $refreshedToken = $cookies[0]->getValue();

        $this->assertNotSame($token, $refreshedToken);
        $this->assertSame(
            $user->getJWTSessionVersion(),
            JWTAuth::setToken($refreshedToken)->getPayload()->get('session_version')
        );
        $this->getUser($refreshedToken)->assertOk()->assertJsonPath('user.id', $user->id);
    }

    private function getUser(string $token): TestResponse
    {
        $this->clearJwtState();

        return $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token]);
    }

    private function clearJwtState(): void
    {
        Auth::forgetGuards();
        JWTAuth::unsetToken();
        app('tymon.jwt')->unsetToken();
    }
}
