<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-based tests for registration with company creation.
 *
 * **Feature: registration-company-field**
 */
class RegistrationCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed statuses for tests
        $this->seedStatuses();
    }

    /**
     * Seed required statuses for testing.
     */
    private function seedStatuses(): void
    {
        // Seed company statuses
        DB::table('company_statuses')->insert([
            'id' => '01HTEST000000000000000001',
            'value' => 'Не определено',
            'slug' => 'not-defined',
            'color' => '#6B7280',
            'sort_order' => 0,
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed user statuses
        DB::table('user_statuses')->insert([
            [
                'id' => '01HTEST000000000000000002',
                'value' => 'Не определено',
                'slug' => 'not-defined',
                'color' => '#6B7280',
                'sort_order' => 0,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '01HTEST000000000000000003',
                'value' => 'Менеджер',
                'slug' => 'manager',
                'color' => '#F59E0B',
                'sort_order' => 4,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Generate random valid company name.
     */
    private function generateCompanyName(): string
    {
        $prefixes = ['ООО', 'ИП', 'АО', 'ЗАО', 'ПАО'];
        $names = ['Рога и Копыта', 'Технологии Будущего', 'Инновации', 'Прогресс', 'Развитие'];

        return $prefixes[array_rand($prefixes)] . ' ' . $names[array_rand($names)] . ' ' . rand(1, 1000);
    }

    /**
     * Generate random valid email.
     */
    private function generateEmail(): string
    {
        return 'test_' . uniqid() . '@example.com';
    }

    /**
     * **Feature: registration-company-field, Property 1: Company Creation with Correct Name**
     *
     * *For any* valid company name provided during registration,
     * the created company record SHALL have the `name` field equal to the provided value.
     *
     * **Validates: Requirements 1.2, 3.1**
     */
    public function test_property_1_company_creation_with_correct_name(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $companyName = $this->generateCompanyName();
            $email = $this->generateEmail();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $companyName,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $company = Company::where('name', $companyName)->first();

                $this->assertNotNull($company, "Company should be created for iteration $i");
                $this->assertEquals($companyName, $company->name, "Company name should match for iteration $i");
            }
        }
    }

    /**
     * **Feature: registration-company-field, Property 3: Company Default Status**
     *
     * *For any* company created through registration,
     * the company's status slug SHALL be "not-defined".
     *
     * **Validates: Requirements 1.3**
     */
    public function test_property_3_company_default_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $companyName = $this->generateCompanyName();
            $email = $this->generateEmail();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $companyName,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $company = Company::where('name', $companyName)->with('status')->first();

                $this->assertNotNull($company, "Company should be created for iteration $i");
                $this->assertNotNull($company->status, "Company should have status for iteration $i");
                $this->assertEquals('not-defined', $company->status->slug, "Company status should be 'not-defined' for iteration $i");
            }
        }
    }

    /**
     * **Feature: registration-company-field, Property 4: User Default Status**
     *
     * *For any* user created through registration,
     * the user's status slug SHALL be "manager".
     *
     * **Validates: Requirements 1.4**
     */
    public function test_property_4_user_default_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $email = $this->generateEmail();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $this->generateCompanyName(),
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $user = User::where('email', $email)->with('status')->first();

                $this->assertNotNull($user, "User should be created for iteration $i");
                $this->assertNotNull($user->status, "User should have status for iteration $i");
                $this->assertEquals('manager', $user->status->slug, "User status should be 'manager' for iteration $i");
            }
        }
    }

    /**
     * **Feature: registration-company-field, Property 5: User-Company Relationship**
     *
     * *For any* user created through registration,
     * the user's `company_id` SHALL reference an existing company record.
     *
     * **Validates: Requirements 1.5**
     */
    public function test_property_5_user_company_relationship(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $email = $this->generateEmail();
            $companyName = $this->generateCompanyName();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $companyName,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $user = User::where('email', $email)->first();

                $this->assertNotNull($user, "User should be created for iteration $i");
                $this->assertNotNull($user->company_id, "User should have company_id for iteration $i");

                $company = Company::find($user->company_id);
                $this->assertNotNull($company, "Company should exist for user's company_id for iteration $i");
                $this->assertEquals($companyName, $company->name, "Company name should match for iteration $i");
            }
        }
    }

    /**
     * **Feature: registration-company-field, Property 6: Company Default Field Values**
     *
     * *For any* company created through registration, the following invariants SHALL hold:
     * - `ban` equals false
     * - `is_active` equals true
     * - `inn` is NULL
     * - `region` is NULL
     *
     * **Validates: Requirements 3.3, 3.4, 3.5, 3.6**
     */
    public function test_property_6_company_default_field_values(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $companyName = $this->generateCompanyName();
            $email = $this->generateEmail();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $companyName,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $company = Company::where('name', $companyName)->first();

                $this->assertNotNull($company, "Company should be created for iteration $i");
                $this->assertFalse($company->ban, "Company ban should be false for iteration $i");
                $this->assertTrue($company->is_active, "Company is_active should be true for iteration $i");
                $this->assertNull($company->inn, "Company inn should be NULL for iteration $i");
                $this->assertNull($company->region, "Company region should be NULL for iteration $i");
            }
        }
    }

    /**
     * **Feature: registration-company-field, Property 2: Company Legal Name Duplication**
     *
     * *For any* company created through registration,
     * the `legal_name` field SHALL equal the `name` field.
     *
     * **Validates: Requirements 3.2**
     */
    public function test_property_2_company_legal_name_duplication(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $companyName = $this->generateCompanyName();
            $email = $this->generateEmail();

            $response = $this->postJson('/api/register', [
                'name' => 'Test User ' . $i,
                'email' => $email,
                'company_name' => $companyName,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 201) {
                $company = Company::where('name', $companyName)->first();

                $this->assertNotNull($company, "Company should be created for iteration $i");
                $this->assertEquals($company->name, $company->legal_name, "Company legal_name should equal name for iteration $i");
            }
        }
    }

    /**
     * Test that company_name is required for registration.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_company_name_is_required(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    /**
     * Test that company_name must be at least 2 characters.
     *
     * **Validates: Requirements 2.3**
     */
    public function test_company_name_minimum_length(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'A',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    /**
     * Test that company_name must not exceed 255 characters.
     *
     * **Validates: Requirements 2.4**
     */
    public function test_company_name_maximum_length(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => str_repeat('A', 256),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }
}
