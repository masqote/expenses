<?php

use App\Models\Category;
use App\Models\TelegramLink;
use App\Models\TelegramSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLinkedUser(string $chatId = '123456789'): User
{
    $user = User::factory()->create();
    TelegramLink::create([
        'user_id'          => $user->id,
        'telegram_chat_id' => $chatId,
        'token_used'       => true,
        'token_expires_at' => now()->subMinutes(1),
    ]);
    return $user;
}

function webhook(string $chatId, string $text): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/telegram/webhook', [
        'message' => ['chat' => ['id' => $chatId], 'text' => $text],
    ]);
}

function callbackQuery(string $chatId, string $data): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/telegram/webhook', [
        'callback_query' => [
            'id'      => 'cq_' . uniqid(),
            'data'    => $data,
            'message' => ['chat' => ['id' => $chatId], 'message_id' => 1],
        ],
    ]);
}

it('records an expense via conversational flow', function () {
    $chatId   = '111000111';
    $user     = createLinkedUser($chatId);
    $category = Category::create(['name' => 'Food', 'icon' => '🍔', 'is_default' => true]);

    // Step 1: choose expense from menu
    callbackQuery($chatId, 'menu:expense')->assertStatus(200);
    expect(TelegramSession::where('chat_id', $chatId)->first()->step)->toBe('expense:category');

    // Step 2: choose category
    callbackQuery($chatId, "cat:{$category->id}:Food")->assertStatus(200);
    expect(TelegramSession::where('chat_id', $chatId)->first()->step)->toBe('expense:description');

    // Step 3: enter description
    webhook($chatId, 'coffee')->assertStatus(200);
    expect(TelegramSession::where('chat_id', $chatId)->first()->step)->toBe('expense:date');

    // Step 4: choose today
    callbackQuery($chatId, 'date:today')->assertStatus(200);
    expect(TelegramSession::where('chat_id', $chatId)->first()->step)->toBe('expense:amount');

    // Step 5: enter amount
    webhook($chatId, '15000')->assertStatus(200);

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'label'   => 'coffee',
        'amount'  => 15000,
    ]);
});

it('records an income via conversational flow', function () {
    $chatId = '222000222';
    $user   = createLinkedUser($chatId);

    callbackQuery($chatId, 'menu:income')->assertStatus(200);
    webhook($chatId, 'freelance')->assertStatus(200);
    callbackQuery($chatId, 'date:today')->assertStatus(200);
    webhook($chatId, '500000')->assertStatus(200);

    $this->assertDatabaseHas('incomes', [
        'user_id' => $user->id,
        'label'   => 'freelance',
        'amount'  => 500000,
    ]);
});

it('records a salary via conversational flow', function () {
    $chatId = '333000333';
    $user   = createLinkedUser($chatId);
    $period = date('Y-m');

    callbackQuery($chatId, 'menu:salary')->assertStatus(200);
    callbackQuery($chatId, "month:{$period}")->assertStatus(200);
    webhook($chatId, '5000000')->assertStatus(200);

    $this->assertDatabaseHas('salaries', [
        'user_id' => $user->id,
        'amount'  => 5000000,
    ]);
});

it('sends linking instructions to unlinked user', function () {
    webhook('999999999', 'hello')->assertStatus(200)->assertJson(['ok' => true]);
    $this->assertDatabaseCount('expenses', 0);
});

it('handles /start command for unlinked user', function () {
    webhook('888888888', '/start')->assertStatus(200)->assertJson(['ok' => true]);
});
