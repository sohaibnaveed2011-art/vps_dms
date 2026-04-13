<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_revokes_token()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['inventory.*']);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->getJson('/api/v1/auth/profile')
            ->assertStatus(401);
    }
}
