<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferralKeyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_helpers_follow_registration_keys_and_detect_cycles(): void
    {
        $a = User::create(['name' => 'A', 'email' => 'a@example.invalid', 'password' => 'password', 'is_active' => true, 'ban' => false]);
        $b = User::create(['name' => 'B', 'email' => 'b@example.invalid', 'password' => 'password', 'is_active' => true, 'ban' => false, 'referrer_key' => $a->key]);
        $service = app(ReferralService::class);
        $this->assertEquals($a->id, $service->getReferrerId($b->id));
        $this->assertEquals(1, $service->getActiveReferralsCount($a->id));
        $this->assertEquals([$b->id], $service->getReferrals($a->id)->pluck('id')->all());
        $this->assertTrue($service->hasCycle($b->id, $a->id));
        $this->assertNull($service->validateReferrer($b->key, $a->id));
        DB::table('users')->where('id', $b->id)->update(['created_at' => now()->subYears(2)]);
        $this->assertFalse($service->isReferralProgramActive($b->id));
        $this->assertEquals(0, $service->getActiveReferralsCount($a->id));
        $this->assertEquals(0, $service->getReferrals(999999)->count());
    }
}
