<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Auth\UserContext;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContextSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_context_rotates_token()
    {
        $user = User::factory()->create();

        $context1 = UserContext::factory()->create([
            'user_id' => $user->id,
            'is_active_context' => true,
        ]);

        $context2 = UserContext::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, ['inventory.*']);

        $oldToken = $user->currentAccessToken()?->token;

        $response = $this->postJson('/api/v1/users/'.$user->id.'/switch-context', [
            'context_id' => $context2->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('user_contexts', [
            'id' => $context2->id,
            'is_active_context' => true,
        ]);
    }
}
