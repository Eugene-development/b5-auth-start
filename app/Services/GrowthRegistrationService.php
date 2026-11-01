<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Reads the shared campaign schema; registration attribution is committed with the new user. */
class GrowthRegistrationService
{
    public function referrerKey(string $token): string
    {
        $campaign = $this->campaign($token);

        return $campaign->owner_key;
    }

    public function attributeUser(string $token, User $user): void
    {
        $campaign = $this->campaign($token, true);
        if ($user->referrer_key !== $campaign->owner_key || (string) $user->id === (string) $campaign->user_id) {
            throw ValidationException::withMessages(['campaign_token' => 'Источник приглашения не совпадает с пригласившим агентом.']);
        }
        DB::table('growth_registrations')->insertOrIgnore(['campaign_id' => $campaign->id, 'user_id' => $user->id, 'created_at' => now()]);
    }

    private function campaign(string $token, bool $lock = false): object
    {
        $query = DB::table('growth_campaigns as c')->join('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.token', $token)->where('c.purpose', 'referral')->where('c.is_active', true)
            ->where('u.is_active', true)->where('u.ban', false)->select('c.id', 'c.user_id', 'u.key as owner_key');
        if ($lock) {
            $query->lockForUpdate();
        }
        $campaign = $query->first();
        if (! $campaign) {
            throw ValidationException::withMessages(['campaign_token' => 'Приглашение недоступно. Запросите новую ссылку.']);
        }

        return $campaign;
    }
}
