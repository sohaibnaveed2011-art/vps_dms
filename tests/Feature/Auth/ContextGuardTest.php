<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContextGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_fails_without_active_context()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['inventory.*']);

        $response = $this->getJson('/api/v1/organizations');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No active context'
            ]);
    }
}
