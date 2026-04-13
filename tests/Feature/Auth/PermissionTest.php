<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Auth\UserContext;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_denied_without_token_ability()
    {
        $user = User::factory()->create();

        UserContext::factory()->create([
            'user_id' => $user->id,
            'is_active_context' => true,
        ]);

        Sanctum::actingAs($user, ['sales.*']);

        $response = $this->postJson('/api/v1/stock-transactions');

        $response->assertStatus(403);
    }
}
