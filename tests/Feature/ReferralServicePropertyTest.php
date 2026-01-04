<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReferralService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based тесты для ReferralService.
 *
 * **Feature: referral-bonus-system**
 *
 * Тестирует корректность работы реферальной системы:
 * - Защита от циклических связей
 * - Валидация рефереров
 * - Ограничение срока действия программы
 */
class ReferralServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referralService = new ReferralService();
    }

    /**
     * Property 2: Защита от циклических реферальных связей
     *
     * *For any* цепочка реферальных связей A→B→C→...→N, попытка установить
     * связь N→A (или любую связь, создающую цикл) должна быть отклонена.
     *
     * **Validates: Requirements 2.1, 2.3**
     *
     * @dataProvider cycleDetectionProvider
     */
    public function test_property_cycle_detection(int $chainLength): void
    {
        // Создаём цепочку пользователей: A → B → C → ... → N
        $users = [];
        $previousUserId = null;

        for ($i = 0; $i < $chainLength; $i++) {
            $user = User::factory()->create([
                'user_id' => $previousUserId,
                'ban' => false,
                'is_active' => true,
            ]);
            $users[] = $user;
            $previousUserId = $user->id;
        }

        // Первый пользователь в цепочке (A)
        $firstUser = $users[0];
        // Последний пользователь в цепочке (N)
        $lastUser = $users[count($users) - 1];

        // Попытка создать цикл: N → A (последний ссылается на первого)
        // Это должно быть обнаружено как цикл
        $hasCycle = $this->referralService->hasCycle($firstUser->id, $lastUser->id);

        $this->assertTrue(
            $hasCycle,
            "Cycle should be detected for chain length {$chainLength}: " .
            "attempting to set user {$firstUser->id} as referrer of user {$lastUser->id}"
        );
    }

    /**
     * Провайдер данных для теста обнаружения циклов.
     * Тестируем цепочки разной длины (от 2 до 10).
     */
    public static function cycleDetectionProvider(): array
    {
        $data = [];
        for ($i = 2; $i <= 10; $i++) {
            $data["chain_length_{$i}"] = [$i];
        }
        return $data;
    }

    /**
     * Property 2: Прямой цикл (A→B, B→A) должен быть обнаружен.
     *
     * **Validates: Requirements 2.1**
     */
    public function test_property_direct_cycle_detection(): void
    {
        // Создаём пользователя A
        $userA = User::factory()->create([
            'user_id' => null,
            'ban' => false,
            'is_active' => true,
        ]);

        // Создаём пользователя B с реферером A
        $userB = User::factory()->create([
            'user_id' => $userA->id,
            'ban' => false,
            'is_active' => true,
        ]);

        // Попытка установить B как реферера A (создаёт цикл A→B→A)
        $hasCycle = $this->referralService->hasCycle($userB->id, $userA->id);

        $this->assertTrue($hasCycle, 'Direct cycle A→B→A should be detected');
    }

    /**
     * Property 2: Самореферальность должна быть отклонена.
     *
     * **Validates: Requirements 2.2**
     */
    public function test_property_self_referral_rejected(): void
    {
        $user = User::factory()->create([
            'ban' => false,
            'is_active' => true,
        ]);

        // Попытка установить себя как реферера
        $validatedReferrerId = $this->referralService->validateReferrer($user->id, $user->id);

        $this->assertNull($validatedReferrerId, 'Self-referral should be rejected');
    }

    /**
     * Property 2: Отсутствие цикла для независимых пользователей.
     *
     * *For any* два независимых пользователя без общей цепочки,
     * установка реферальной связи должна быть разрешена.
     */
    public function test_property_no_cycle_for_independent_users(): void
    {
        // Создаём двух независимых пользователей
        $userA = User::factory()->create([
            'user_id' => null,
            'ban' => false,
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'user_id' => null,
            'ban' => false,
            'is_active' => true,
        ]);

        // Проверяем, что цикла нет
        $hasCycle = $this->referralService->hasCycle($userA->id, $userB->id);

        $this->assertFalse($hasCycle, 'No cycle should exist for independent users');
    }

    /**
     * Property 1: Валидация существующего реферера.
     *
     * *For any* существующий активный пользователь, validateReferrer
     * должен вернуть его ID.
     *
     * **Validates: Requirements 1.1**
     *
     * @dataProvider validReferrerProvider
     */
    public function test_property_valid_referrer_accepted(int $iteration): void
    {
        // Создаём случайного активного пользователя
        $referrer = User::factory()->create([
            'ban' => false,
            'is_active' => true,
        ]);

        // Валидируем реферера (новый пользователь ещё не создан, ID = 0)
        $validatedId = $this->referralService->validateReferrer($referrer->id, 0);

        $this->assertEquals(
            $referrer->id,
            $validatedId,
            "Valid referrer should be accepted (iteration {$iteration})"
        );
    }

    /**
     * Провайдер для теста валидации реферера (100 итераций).
     */
    public static function validReferrerProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data["iteration_{$i}"] = [$i];
        }
        return $data;
    }

    /**
     * Property 1: Несуществующий реферер должен быть отклонён.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_property_nonexistent_referrer_rejected(): void
    {
        $nonExistentId = 999999;

        $validatedId = $this->referralService->validateReferrer($nonExistentId, 0);

        $this->assertNull($validatedId, 'Non-existent referrer should be rejected');
    }

    /**
     * Property 1: Забаненный реферер должен быть отклонён.
     */
    public function test_property_banned_referrer_rejected(): void
    {
        $bannedUser = User::factory()->create([
            'ban' => true,
            'is_active' => true,
        ]);

        $validatedId = $this->referralService->validateReferrer($bannedUser->id, 0);

        $this->assertNull($validatedId, 'Banned referrer should be rejected');
    }

    /**
     * Property 1: Неактивный реферер должен быть отклонён.
     */
    public function test_property_inactive_referrer_rejected(): void
    {
        $inactiveUser = User::factory()->create([
            'ban' => false,
            'is_active' => false,
        ]);

        $validatedId = $this->referralService->validateReferrer($inactiveUser->id, 0);

        $this->assertNull($validatedId, 'Inactive referrer should be rejected');
    }

    /**
     * Property 7: Проверка срока действия реферальной программы.
     *
     * *For any* реферал, зарегистрированный менее 2 лет назад,
     * isReferralProgramActive должен вернуть true.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_referral_program_active_within_2_years(): void
    {
        // Создаём пользователя, зарегистрированного 1 год назад
        $user = User::factory()->create([
            'created_at' => Carbon::now()->subYear(),
        ]);

        $isActive = $this->referralService->isReferralProgramActive($user->id);

        $this->assertTrue($isActive, 'Referral program should be active within 2 years');
    }

    /**
     * Property 7: Реферальная программа истекает через 2 года.
     *
     * *For any* реферал, зарегистрированный более 2 лет назад,
     * isReferralProgramActive должен вернуть false.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_referral_program_expired_after_2_years(): void
    {
        // Создаём пользователя, зарегистрированного 3 года назад
        $user = User::factory()->create([
            'created_at' => Carbon::now()->subYears(3),
        ]);

        $isActive = $this->referralService->isReferralProgramActive($user->id);

        $this->assertFalse($isActive, 'Referral program should be expired after 2 years');
    }

    /**
     * Property 7: Граничный случай - ровно 2 года.
     */
    public function test_property_referral_program_boundary_2_years(): void
    {
        // Создаём пользователя, зарегистрированного ровно 2 года и 1 день назад
        $user = User::factory()->create([
            'created_at' => Carbon::now()->subYears(2)->subDay(),
        ]);

        $isActive = $this->referralService->isReferralProgramActive($user->id);

        $this->assertFalse($isActive, 'Referral program should be expired at 2 years boundary');
    }
}


/**
 * Тесты для интеграции реферальной системы с регистрацией.
 */
class ReferralRegistrationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: Сохранение реферальной связи при регистрации.
     *
     * *For any* новый пользователь, зарегистрированный с валидным параметром `ref`,
     * поле `user_id` в таблице users должно содержать ID указанного реферера.
     *
     * **Validates: Requirements 1.1**
     *
     * @dataProvider registrationWithReferrerProvider
     */
    public function test_property_registration_saves_referrer(int $iteration): void
    {
        // Создаём реферера
        $referrer = User::factory()->create([
            'ban' => false,
            'is_active' => true,
        ]);

        // Регистрируем нового пользователя с ref параметром
        $response = $this->postJson('/api/auth/register', [
            'name' => "Test User {$iteration}",
            'email' => "test{$iteration}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ref' => $referrer->id,
        ]);

        $response->assertStatus(201);

        // Проверяем, что user_id сохранён
        $newUser = User::where('email', "test{$iteration}@example.com")->first();

        $this->assertNotNull($newUser, 'New user should be created');
        $this->assertEquals(
            $referrer->id,
            $newUser->user_id,
            "User's referrer ID should be saved (iteration {$iteration})"
        );
    }

    /**
     * Провайдер для теста регистрации с реферером (100 итераций).
     */
    public static function registrationWithReferrerProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data["iteration_{$i}"] = [$i];
        }
        return $data;
    }

    /**
     * Property 1: Регистрация без ref параметра.
     *
     * *For any* новый пользователь без параметра `ref`,
     * поле `user_id` должно быть NULL.
     *
     * **Validates: Requirements 1.3**
     */
    public function test_property_registration_without_referrer(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User No Ref',
            'email' => 'noref@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'noref@example.com')->first();

        $this->assertNotNull($newUser, 'New user should be created');
        $this->assertNull($newUser->user_id, 'User without ref should have NULL user_id');
    }

    /**
     * Property 1: Регистрация с несуществующим ref.
     *
     * *For any* несуществующий ID в параметре `ref`,
     * регистрация должна завершиться успешно с NULL user_id.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_property_registration_with_invalid_referrer(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User Invalid Ref',
            'email' => 'invalidref@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ref' => 999999, // Несуществующий ID
        ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'invalidref@example.com')->first();

        $this->assertNotNull($newUser, 'New user should be created');
        $this->assertNull($newUser->user_id, 'User with invalid ref should have NULL user_id');
    }
}
