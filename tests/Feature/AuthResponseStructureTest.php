<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthResponseStructureTest extends TestCase
{
    public function test_login_error_response_structure()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'short'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'email'
                    ]
                ])
                ->assertJson([
                    'success' => false
                ]);
    }

    public function test_register_error_response_structure()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'The given data was invalid.'
                ]);
    }

    public function test_unauthenticated_user_endpoint_response()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_logout_endpoint_response()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_csrf_cookie_endpoint_exists()
    {
        $response = $this->get('/sanctum/csrf-cookie');

        // Should not return 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}
