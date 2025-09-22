<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    public function test_login_endpoint_exists()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should return 422 for validation or authentication error, not 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_register_endpoint_exists()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should return some response, not 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_user_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/user');

        // Should return 401 for unauthenticated access
        $response->assertStatus(401);
    }

    public function test_logout_endpoint_requires_authentication()
    {
        $response = $this->postJson('/api/logout');

        // Should return 401 for unauthenticated access
        $response->assertStatus(401);
    }

    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'The given data was invalid.'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }

    public function test_register_validates_required_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'The given data was invalid.'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }
}
