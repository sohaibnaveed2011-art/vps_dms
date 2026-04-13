<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Auth\UserContext;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_and_receive_token()
    {
        // Create required organization
        $organization = Organization::factory()->create();

        // Create active user
        $user = User::factory()->create([
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);

        // 🔑 Create active context (REQUIRED)
        UserContext::factory()->create([
            'user_id'            => $user->id,
            'organization_id'    => $organization->id,
            'is_active_context'  => true,
        ]);

        // Attempt login
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        // Assertions
        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'email',
                ],
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'fake@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error'  => true,
            ]);
    }
}
