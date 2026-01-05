<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления реферальными связями.
 *
 * Отвечает за:
 * - Валидацию реферера при регистрации
 * - Защиту от циклических реферальных связей
 * - Проверку срока действия реферальной программы
 */
class ReferralService
{
    /**
     * Срок действия реферальной программы в годах.
     */
    const REFERRAL_PROGRAM_YEARS = 2;

    /**
     * Максимальная глубина проверки цепочки рефералов.
     */
    const MAX_CHAIN_DEPTH = 10;

    /**
     * Валидировать и получить ID реферера.
     *
     * @param string|null $referrerKey Ключ потенциального реферера
     * @param int $newUserId ID нового пользователя (0 если ещё не создан)
     * @return int|null Валидный ID реферера или null
     */
    public function validateReferrer($referrerKey, int $newUserId = 0): ?int
    {
        // Проверяем, что referrerKey передан и не пуст
        if ($referrerKey === null || $referrerKey === '') {
            return null;
        }

        // Находим реферера по key
        $referrer = User::where('key', $referrerKey)->first();
        if (!$referrer) {
            Log::info('ReferralService: Referrer not found', [
                'referrer_key' => $referrerKey
            ]);
            return null;
        }

        // Проверяем, что это не самореферальность
        if ($newUserId > 0 && $referrer->id === $newUserId) {
            Log::info('ReferralService: Self-referral attempt rejected', [
                'referrer_id' => $referrer->id,
                'new_user_id' => $newUserId
            ]);
            return null;
        }

        // Проверяем, что реферер активен и не забанен
        if ($referrer->ban || !$referrer->is_active) {
            Log::info('ReferralService: Referrer is banned or inactive', [
                'referrer_id' => $referrer->id,
                'ban' => $referrer->ban,
                'is_active' => $referrer->is_active
            ]);
            return null;
        }

        // Проверяем на циклы (если новый пользователь уже существует)
        if ($newUserId > 0 && $this->hasCycle($referrer->id, $newUserId)) {
            Log::warning('ReferralService: Cycle detected in referral chain', [
                'referrer_id' => $referrer->id,
                'new_user_id' => $newUserId
            ]);
            return null;
        }

        Log::info('ReferralService: Referrer validated successfully', [
            'referrer_id' => $referrer->id,
            'referrer_name' => $referrer->name
        ]);

        return $referrer->id;
    }

    /**
     * Проверить наличие цикла в реферальной цепочке.
     *
     * Цикл возникает, если потенциальный реферер уже является
     * рефералом нового пользователя (прямо или через цепочку).
     *
     * @param int $referrerId ID потенциального реферера
     * @param int $referralId ID нового пользователя (реферала)
     * @param int $maxDepth Максимальная глубина проверки
     * @return bool true если цикл обнаружен
     */
    public function hasCycle(int $referrerId, int $referralId, int $maxDepth = self::MAX_CHAIN_DEPTH): bool
    {
        // Проверяем прямой цикл (A→B, B→A)
        if ($referrerId === $referralId) {
            return true;
        }

        // Проходим по цепочке рефереров от referrerId вверх
        // Если встретим referralId - значит цикл
        $currentId = $referrerId;
        $visited = [$referralId]; // Начинаем с referralId как уже посещённого
        $depth = 0;

        while ($currentId !== null && $depth < $maxDepth) {
            // Если текущий ID уже посещён - цикл
            if (in_array($currentId, $visited)) {
                return true;
            }

            $visited[] = $currentId;

            // Получаем реферера текущего пользователя
            $user = User::find($currentId);
            if (!$user) {
                break;
            }

            $currentId = $user->user_id; // user_id - это ID реферера
            $depth++;
        }

        return false;
    }

    /**
     * Проверить, не истёк ли срок реферальной программы для реферала.
     *
     * Реферальные бонусы начисляются только в течение 2 лет
     * после регистрации реферала.
     *
     * @param int $referralId ID реферала
     * @return bool true если срок не истёк (менее 2 лет)
     */
    public function isReferralProgramActive(int $referralId): bool
    {
        $referral = User::find($referralId);
        if (!$referral) {
            return false;
        }

        $registrationDate = Carbon::parse($referral->created_at);
        $expirationDate = $registrationDate->addYears(self::REFERRAL_PROGRAM_YEARS);

        return Carbon::now()->lt($expirationDate);
    }

    /**
     * Получить ID реферера для пользователя.
     *
     * @param int $userId ID пользователя
     * @return int|null ID реферера или null
     */
    public function getReferrerId(int $userId): ?int
    {
        $user = User::find($userId);
        return $user?->user_id;
    }

    /**
     * Получить список рефералов пользователя.
     *
     * @param int $referrerId ID реферера
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReferrals(int $referrerId)
    {
        return User::where('user_id', $referrerId)->get();
    }

    /**
     * Получить количество активных рефералов.
     *
     * Активный реферал - это реферал, зарегистрированный менее 2 лет назад.
     *
     * @param int $referrerId ID реферера
     * @return int
     */
    public function getActiveReferralsCount(int $referrerId): int
    {
        $cutoffDate = Carbon::now()->subYears(self::REFERRAL_PROGRAM_YEARS);

        return User::where('user_id', $referrerId)
            ->where('created_at', '>=', $cutoffDate)
            ->count();
    }
}
