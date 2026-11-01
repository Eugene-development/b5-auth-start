<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GrowthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(bool $active = true): array
    {
        $owner = User::create(['name' => 'Inviter', 'email' => Str::ulid().'@example.invalid', 'password' => 'password',
            'status_id' => DB::table('user_statuses')->where('slug', 'agent')->value('id'), 'is_active' => true, 'ban' => false]);
        $id = (string) Str::ulid();
        $token = bin2hex(random_bytes(24));
        DB::table('growth_campaigns')->insert(['id' => $id, 'user_id' => $owner->id, 'token' => $token,
            'name' => 'Telegram', 'channel' => 'telegram', 'purpose' => 'referral', 'is_active' => $active, 'created_at' => now(), 'updated_at' => now()]);

        return [$owner, $id, $token];
    }

    private function payload(string $token): array
    {
        return ['name' => 'New agent', 'email' => 'new@example.invalid', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'campaign_token' => $token];
    }

    public function test_registration_resolves_referrer_internally_and_commits_source_once(): void
    {
        [$owner, $id, $token] = $this->invitation();
        $this->postJson('/api/register', $this->payload($token))->assertCreated();
        $user = User::where('email', 'new@example.invalid')->firstOrFail();
        $this->assertSame((string) $owner->key, $user->referrer_key);
        $this->assertDatabaseHas('growth_registrations', ['user_id' => $user->id, 'campaign_id' => $id]);
        $this->postJson('/api/register', $this->payload($token))->assertUnprocessable();
        $this->assertDatabaseCount('growth_registrations', 1);
    }

    public function test_disabled_invitation_does_not_create_unattributed_user(): void
    {
        [, , $token] = $this->invitation(false);
        $this->postJson('/api/register', $this->payload($token))->assertUnprocessable()->assertJsonValidationErrors('campaign_token');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.invalid']);
    }

    public function test_conflicting_referrer_cannot_hijack_campaign_attribution(): void
    {
        [, , $token] = $this->invitation();
        $this->postJson('/api/register', $this->payload($token) + ['ref' => (string) Str::ulid()])->assertUnprocessable()->assertJsonValidationErrors('ref');
        $this->assertDatabaseCount('growth_registrations', 0);
    }
}
