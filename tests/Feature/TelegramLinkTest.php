<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('generates a telegram link token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/telegram/link-token');

    $response->assertStatus(200);
    $response->assertJsonStructure(['token', 'expires_at']);
    expect($response->json('token'))->not->toBeEmpty();
});

it('links account via /link webhook and verifies account is linked', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Generate token
    $tokenRes = $this->postJson('/api/telegram/link-token');
    $token    = $tokenRes->json('token');

    // Simulate /link <token> webhook from Telegram
    $chatId = '123456789';
    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => $chatId],
            'text' => "/link {$token}",
        ],
    ])->assertStatus(200);

    // Verify account is linked
    $this->assertDatabaseHas('telegram_links', [
        'user_id'          => $user->id,
        'telegram_chat_id' => $chatId,
        'token_used'       => true,
    ]);
});

it('rejects a second use of the same token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $token  = $this->postJson('/api/telegram/link-token')->json('token');
    $chatId = '111222333';

    // First use — should succeed
    $this->postJson('/api/telegram/webhook', [
        'message' => ['chat' => ['id' => $chatId], 'text' => "/link {$token}"],
    ]);

    // Second use — should fail (token already used)
    $chatId2 = '999888777';
    $this->postJson('/api/telegram/webhook', [
        'message' => ['chat' => ['id' => $chatId2], 'text' => "/link {$token}"],
    ]);

    // Original link should remain unchanged
    $this->assertDatabaseHas('telegram_links', [
        'user_id'          => $user->id,
        'telegram_chat_id' => $chatId,
    ]);
    $this->assertDatabaseMissing('telegram_links', [
        'telegram_chat_id' => $chatId2,
    ]);
});
