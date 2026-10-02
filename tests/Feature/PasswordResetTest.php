<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private string $initialAuthGuard;

    public function createApplication()
    {
        $app = parent::createApplication();
        // Keep even direct invocations isolated from the configured shared database.
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'mail.default' => 'array',
        ]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->initialAuthGuard = config('auth.defaults.guard');
        Notification::fake();
        $this->freezeTime();
        config([
            'app.frontend_url' => 'https://agent.example',
            'auth.password_reset.allowed_origins' => ['https://agent.example', 'https://admin.example'],
        ]);
    }

    public function test_forgot_password_does_not_reveal_whether_an_email_exists(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $existing = $this->postJson('/api/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'unknown@example.com']);

        $existing->assertOk()->assertJson(['success' => true]);
        $unknown->assertOk()->assertExactJson($existing->json());
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        Notification::assertCount(1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'unknown@example.com']);
    }

    public function test_emailed_token_resets_password_once_and_is_stored_only_as_a_hash(): void
    {
        $user = User::factory()->create(['email' => 'reset+agent@example.com']);
        $oldHash = $user->password;
        $oldRememberToken = $user->remember_token;

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $notification = $this->sentResetNotification($user);
        $tokenRecord = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($tokenRecord);
        $this->assertNotSame($notification->token, $tokenRecord->token);
        $this->assertTrue(Hash::check($notification->token, $tokenRecord->token));

        $this->postJson('/api/reset-password', $this->resetPayload($user, $notification->token))
            ->assertOk()
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertNotSame($oldRememberToken, $user->remember_token);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->postJson('/api/reset-password', $this->resetPayload($user, $notification->token, 'another-password-123'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_new_reset_email_invalidates_the_previous_link(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $first = $this->sentResetNotification($user);
        $this->travel(61)->seconds();
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $second = Notification::sent($user, ResetPasswordNotification::class)->last();

        $this->assertNotSame($first->token, $second->token);
        $this->assertSame(1, DB::table('password_reset_tokens')->where('email', $user->email)->count());

        $this->postJson('/api/reset-password', $this->resetPayload($user, $first->token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->postJson('/api/reset-password', $this->resetPayload($user, $second->token))->assertOk();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_password_reset_revokes_the_existing_jwt_and_allows_a_new_login(): void
    {
        config(['jwt.secret' => str_repeat('test-reset-signing-key-', 3)]);
        $user = User::factory()->create([
            'email' => 'session-reset@example.com',
            'remember_token' => null,
        ]);

        $this->clearJwtState();
        $login = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        // Status checks avoid including issued JWTs in failed assertion output.
        $this->assertSame(200, $login->getStatusCode(), 'The original credentials must allow login.');
        $oldToken = $login->json('token');
        $this->assertIsString($oldToken);

        $this->clearJwtState();
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $resetNotification = $this->sentResetNotification($user);

        $this->clearJwtState();
        $this->postJson('/api/reset-password', $this->resetPayload($user, $resetNotification->token))
            ->assertOk();

        $this->clearJwtState();
        $oldSession = $this->getJson('/api/user', ['Authorization' => 'Bearer '.$oldToken]);
        $this->assertSame(401, $oldSession->getStatusCode(), 'The original JWT must be rejected after reset.');
        $this->clearJwtState();
        $oldRefresh = $this->postJson('/api/refresh', [], ['Authorization' => 'Bearer '.$oldToken]);
        $this->assertSame(401, $oldRefresh->getStatusCode(), 'A revoked JWT must not be refreshed.');

        $this->clearJwtState();
        $oldLogin = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertSame(422, $oldLogin->getStatusCode(), 'The old password must no longer allow login.');

        $this->clearJwtState();
        $newLogin = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'new-password-123']);
        $this->assertSame(200, $newLogin->getStatusCode(), 'The new password must allow login.');
        $newToken = $newLogin->json('token');
        $this->assertIsString($newToken);

        $this->clearJwtState();
        $newSession = $this->getJson('/api/user', ['Authorization' => 'Bearer '.$newToken]);
        $this->assertSame(200, $newSession->getStatusCode(), 'The new JWT must allow access.');
        $this->assertSame($user->id, $newSession->json('user.id'));
        $this->clearJwtState();
        $newRefresh = $this->postJson('/api/refresh', [], ['Authorization' => 'Bearer '.$newToken]);
        $this->assertSame(200, $newRefresh->getStatusCode(), 'The new JWT must allow refresh.');
    }

    public function test_repeated_reset_request_preserves_the_recent_link(): void
    {
        $user = User::factory()->create();

        $firstResponse = $this->postJson('/api/forgot-password', ['email' => $user->email]);
        $firstNotification = $this->sentResetNotification($user);
        $tokenHash = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $secondResponse = $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $firstResponse->assertOk();
        $secondResponse->assertOk()->assertExactJson($firstResponse->json());
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        $this->assertSame($tokenHash, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));

        $this->postJson('/api/reset-password', $this->resetPayload($user, $firstNotification->token))->assertOk();
    }

    public function test_reset_request_throttle_uses_the_configured_duration(): void
    {
        config(['auth.passwords.users.throttle' => 10]);
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $first = $this->sentResetNotification($user);
        $this->travel(11)->seconds();
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $second = Notification::sent($user, ResetPasswordNotification::class)->last();

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 2);
        $this->assertNotSame($first->token, $second->token);
    }

    public function test_mail_delivery_failure_does_not_disclose_an_existing_email(): void
    {
        $user = User::factory()->create();
        $dispatcher = $this->mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('Simulated mail failure'));

        $existing = $this->postJson('/api/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'unknown@example.com']);

        $existing->assertOk()->assertJson(['success' => true]);
        $unknown->assertOk()->assertExactJson($existing->json());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // A failed delivery must allow an immediate retry with a fresh notification.
        $this->app->instance(Dispatcher::class, Notification::fake());
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_incorrect_token_preserves_the_password_and_valid_link(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);

        $this->postJson('/api/reset-password', $this->resetPayload($user, str_repeat('b', 64)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_token_cannot_be_used_for_a_different_email(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($owner, $token);

        $this->postJson('/api/reset-password', $this->resetPayload($other, $token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('password', $owner->fresh()->password));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $owner->email]);
    }

    public function test_expired_token_cannot_change_the_password(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token, now()->subMinutes(61));

        $this->postJson('/api/reset-password', $this->resetPayload($user, $token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_token_expiration_uses_the_configured_duration(): void
    {
        config(['auth.passwords.users.expire' => 5]);
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token, now()->subMinutes(6));

        $this->postJson('/api/reset-password', $this->resetPayload($user, $token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_token_without_a_creation_date_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => null]);

        $this->postJson('/api/reset-password', $this->resetPayload($user, $token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_orphaned_token_returns_the_same_invalid_token_error(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);
        $user->delete();

        $this->postJson('/api/reset-password', $this->resetPayload($user, $token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_password_change_is_rolled_back_if_token_consumption_fails(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);

        // Simulate a database failure after the password update without mocking transactions.
        DB::statement("CREATE TRIGGER reject_token_delete BEFORE DELETE ON password_reset_tokens BEGIN SELECT RAISE(ABORT, 'Token deletion failed'); END");

        try {
            $this->postJson('/api/reset-password', $this->resetPayload($user, $token))
                ->assertStatus(500)
                ->assertJson(['success' => false]);

            $this->assertTrue(Hash::check('password', $user->fresh()->password));
            $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        } finally {
            DB::statement('DROP TRIGGER reject_token_delete');
        }
    }

    public function test_password_confirmation_must_match_before_consuming_the_token(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);
        $payload = $this->resetPayload($user, $token);
        $payload['password_confirmation'] = 'different-password';

        $this->postJson('/api/reset-password', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_bcrypt_password_at_the_72_byte_limit_can_be_reset(): void
    {
        config(['hashing.driver' => 'bcrypt']);
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $password = str_repeat('p', 72);
        $this->storeToken($user, $token);

        $this->postJson('/api/reset-password', $this->resetPayload($user, $token, $password))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    #[DataProvider('invalidResetInputs')]
    public function test_malformed_reset_input_returns_validation_errors(string $field, mixed $value): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 64);
        $this->storeToken($user, $token);
        $payload = $this->resetPayload($user, $token);
        $payload[$field] = $value;
        if ($field === 'password') {
            $payload['password_confirmation'] = $value;
        }

        $this->postJson('/api/reset-password', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public static function invalidResetInputs(): array
    {
        return [
            'array token' => ['token', ['unexpected' => 'token']],
            'numeric token' => ['token', 12345678],
            'oversized token' => ['token', str_repeat('a', 4097)],
            'array email' => ['email', ['unexpected@example.com']],
            'invalid email' => ['email', 'invalid-email'],
            'short password' => ['password', 'short'],
            'array password' => ['password', ['unexpected' => 'password']],
            'bcrypt password exceeds 72 ASCII bytes' => ['password', str_repeat('p', 73)],
            'bcrypt password exceeds 72 UTF-8 bytes' => ['password', str_repeat('я', 37)],
            'password contains NUL' => ['password', "valid-password\0suffix"],
        ];
    }

    public function test_recovery_link_uses_the_trusted_origin_of_the_current_frontend(): void
    {
        $user = User::factory()->create([
            'email' => 'reset+agent@example.com',
            'registration_domain' => 'https://admin.example',
        ]);

        $this->withHeader('Origin', 'https://agent.example')
            ->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk();

        $notification = $this->sentResetNotification($user);
        $resetUrl = $notification->toMail($user)->viewData['resetUrl'];
        $this->assertSame('https://agent.example', parse_url($resetUrl, PHP_URL_SCHEME).'://'.parse_url($resetUrl, PHP_URL_HOST));
        $this->assertSame('/reset-password', parse_url($resetUrl, PHP_URL_PATH));
        parse_str(parse_url($resetUrl, PHP_URL_QUERY), $query);
        $this->assertSame($notification->token, $query['token']);
        $this->assertSame($user->email, $query['email']);
    }

    public function test_untrusted_request_origin_uses_the_trusted_registration_domain(): void
    {
        $user = User::factory()->create(['registration_domain' => 'https://admin.example']);

        $this->withHeader('Origin', 'https://attacker.example')
            ->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk();

        $resetUrl = $this->sentResetNotification($user)->toMail($user)->viewData['resetUrl'];
        $this->assertStringStartsWith('https://admin.example/reset-password?', $resetUrl);
    }

    #[DataProvider('untrustedRegistrationDomains')]
    public function test_recovery_link_never_includes_an_untrusted_registration_domain(?string $domain): void
    {
        $user = User::factory()->create(['registration_domain' => $domain]);

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $resetUrl = $this->sentResetNotification($user)->toMail($user)->viewData['resetUrl'];
        $this->assertStringStartsWith('https://agent.example/reset-password?', $resetUrl);
    }

    public static function untrustedRegistrationDomains(): array
    {
        return [
            'missing domain' => [null],
            'empty domain' => [''],
            'external domain' => ['https://attacker.example'],
            'lookalike host' => ['https://agent.example.attacker.example'],
            'userinfo host confusion' => ['https://agent.example@attacker.example'],
            'trusted host with path' => ['https://admin.example/redirect'],
            'trusted host with query' => ['https://admin.example?redirect=https://attacker.example'],
            'unsupported scheme' => ['javascript:alert(1)'],
        ];
    }

    private function sentResetNotification(User $user): ResetPasswordNotification
    {
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        return Notification::sent($user, ResetPasswordNotification::class)->first();
    }

    private function clearJwtState(): void
    {
        JWTAuth::unsetToken();
        app('tymon.jwt')->unsetToken();
        Auth::shouldUse($this->initialAuthGuard);
        Auth::forgetGuards();

        // JWTAuth's adapter keeps a guard instance across PHPUnit HTTP requests.
        $this->app->forgetInstance('auth.driver');
        $this->app->forgetInstance('tymon.jwt.provider.auth');
        $this->app->forgetInstance('tymon.jwt.auth');
        JWTAuth::clearResolvedInstance('tymon.jwt.auth');
    }

    private function resetPayload(User $user, string $token, string $password = 'new-password-123'): array
    {
        return [
            'email' => $user->email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    private function storeToken(User $user, string $token, mixed $createdAt = null): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
