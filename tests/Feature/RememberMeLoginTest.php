<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Mockery;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class RememberMeLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureAuth();
        $this->freezeTime();
    }

    private function configureAuth(): void
    {
        config([
            'jwt.secret' => str_repeat('test-only-remember-signing-key-', 3),
            'jwt.ttl' => 90,
            'cache.default' => 'array',
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        // Exercise the real HTTP middleware, controller and JWT signing without a database.
        $user = new User;
        $user->forceFill([
            'id' => 987654321,
            'email' => 'remember-check@example.invalid',
            'ban' => false,
            'is_active' => true,
            'remember_token' => null,
        ]);
        $user->setRelation('status', null);
        $user->setRelation('phones', new Collection);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->andReturn($user);
        $provider->shouldReceive('retrieveById')->andReturn($user);
        $provider->shouldReceive('validateCredentials')->andReturn(true);
        $provider->shouldReceive('rehashPasswordIfRequired')->andReturnNull();
        Auth::guard('web')->setProvider($provider);
        Auth::guard('api')->setProvider($provider);
    }

    public function test_unchecked_remember_uses_a_session_cookie_and_the_configured_jwt_lifetime(): void
    {
        $response = $this->login(['remember' => false]);

        $this->assertJwtLifetime($response, 90 * 60);
        $this->assertRememberClaim($response, false);
        $this->assertSessionCookie($this->authCookie($response));
    }

    public function test_omitting_remember_uses_a_session_cookie(): void
    {
        $response = $this->login();

        $this->assertJwtLifetime($response, 90 * 60);
        $this->assertSessionCookie($this->authCookie($response));
    }

    public function test_checked_remember_keeps_the_cookie_and_jwt_for_thirty_days(): void
    {
        $response = $this->login(['remember' => true]);

        $this->assertJwtLifetime($response, 30 * 24 * 60 * 60);
        $this->assertRememberClaim($response, true);
        $cookie = $this->authCookie($response);
        $this->assertSame(now()->addDays(30)->getTimestamp(), $cookie->getExpiresTime());
        $this->assertStringContainsString('Max-Age=', (string) $cookie);
    }

    public function test_unchecking_remember_after_a_remembered_login_resets_the_cookie_and_jwt_lifetime(): void
    {
        $remembered = $this->login(['remember' => true]);
        $this->assertJwtLifetime($remembered, 30 * 24 * 60 * 60);

        $response = $this->login(['remember' => false]);

        $this->assertJwtLifetime($response, 90 * 60);
        $this->assertSessionCookie($this->authCookie($response));
    }

    public function test_refresh_keeps_an_unchecked_login_in_a_session_cookie_and_ignores_request_remember(): void
    {
        $login = $this->login(['remember' => false]);

        $response = $this->refresh($login->json('token'), ['remember' => true]);

        $this->assertJwtLifetime($response, 90 * 60);
        $this->assertRememberClaim($response, false);
        $this->assertSessionCookie($this->authCookie($response));
    }

    public function test_refresh_preserves_a_remembered_login_and_its_claim_across_repeated_refreshes(): void
    {
        $login = $this->login(['remember' => true]);

        $firstRefresh = $this->refresh($login->json('token'), ['remember' => false]);
        $this->assertJwtLifetime($firstRefresh, 30 * 24 * 60 * 60);
        $this->assertRememberClaim($firstRefresh, true);
        $cookie = $this->authCookie($firstRefresh);
        $this->assertSame(now()->addDays(30)->getTimestamp(), $cookie->getExpiresTime());

        $secondRefresh = $this->refresh($cookie->getValue());
        $this->assertJwtLifetime($secondRefresh, 30 * 24 * 60 * 60);
        $this->assertRememberClaim($secondRefresh, true);
        $this->assertSame(now()->addDays(30)->getTimestamp(), $this->authCookie($secondRefresh)->getExpiresTime());
    }

    public function test_refresh_treats_a_legacy_token_without_remember_as_a_session(): void
    {
        $login = $this->login(['remember' => true]);
        $claims = JWTAuth::setToken($login->json('token'))->getPayload()->toArray();
        unset($claims['remember']);
        $legacyToken = app('tymon.jwt.provider.jwt')->encode($claims);

        $response = $this->refresh($legacyToken, ['remember' => true]);

        $this->assertJwtLifetime($response, 90 * 60);
        $this->assertSessionCookie($this->authCookie($response));
    }

    private function login(array $options = []): TestResponse
    {
        JWTAuth::unsetToken();
        app('tymon.jwt')->unsetToken();

        return $this->postJson('/api/login', [
            'email' => 'remember-check@example.invalid',
            'password' => 'test-only-password',
            ...$options,
        ], ['Origin' => 'https://rubonus.pro'])->assertOk();
    }

    private function assertJwtLifetime(TestResponse $response, int $seconds): void
    {
        $response->assertJsonPath('expires_in', $seconds);
        $claims = JWTAuth::setToken($this->responseToken($response))->getPayload();
        $this->assertSame($seconds, $claims->get('exp') - $claims->get('iat'));
    }

    private function refresh(string $token, array $options = []): TestResponse
    {
        // Each refresh is a separate application request, with no inherited JWT factory state.
        $this->refreshApplication();
        $this->configureAuth();

        return $this->call('POST', '/api/refresh', $options, ['b5_auth_token' => $token], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ORIGIN' => 'https://rubonus.pro',
        ])->assertOk();
    }

    private function responseToken(TestResponse $response): string
    {
        return $response->json('token') ?? $response->headers->getCookies()[0]->getValue();
    }

    private function assertRememberClaim(TestResponse $response, bool $remember): void
    {
        $claims = JWTAuth::setToken($this->responseToken($response))->getPayload();
        $this->assertSame($remember, $claims->get('remember'));
    }

    private function authCookie(TestResponse $response): Cookie
    {
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];
        $this->assertSame('b5_auth_token', $cookie->getName());
        $this->assertSame($this->responseToken($response), $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame('.rubonus.pro', $cookie->getDomain());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());

        return $cookie;
    }

    private function assertSessionCookie(Cookie $cookie): void
    {
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertStringNotContainsString('expires=', strtolower((string) $cookie));
        $this->assertStringNotContainsString('max-age=', strtolower((string) $cookie));
    }
}
