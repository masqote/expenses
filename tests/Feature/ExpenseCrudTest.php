<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates an expense and returns 201', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/expenses', [
        'label'  => 'coffee',
        'amount' => 15000,
        'period' => '2025-06',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['label' => 'coffee', 'period' => '2025-06']);
});

it('lists expenses for a period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/expenses', ['label' => 'rent', 'amount' => 500000, 'period' => '2025-06']);
    $this->postJson('/api/expenses', ['label' => 'food', 'amount' => 100000, 'period' => '2025-06']);
    $this->postJson('/api/expenses', ['label' => 'other', 'amount' => 50000, 'period' => '2025-05']);

    $response = $this->getJson('/api/expenses?period=2025-06');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(2);
});

it('updates an expense', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/expenses', ['label' => 'coffee', 'amount' => 15000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $response = $this->putJson("/api/expenses/{$id}", ['label' => 'latte', 'amount' => 20000]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['label' => 'latte', 'amount' => '20000.00']);
});

it('deletes an expense and returns 204', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/expenses', ['label' => 'coffee', 'amount' => 15000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $this->deleteJson("/api/expenses/{$id}")->assertStatus(204);
});

it('returns 404 after deleting an expense', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/expenses', ['label' => 'coffee', 'amount' => 15000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $this->deleteJson("/api/expenses/{$id}");

    $this->putJson("/api/expenses/{$id}", ['label' => 'x'])->assertStatus(404);
});

it('returns 403 when updating another user expense', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($owner);

    $create = $this->postJson('/api/expenses', ['label' => 'coffee', 'amount' => 15000, 'period' => '2025-06']);
    $id     = $create->json('id');

    Sanctum::actingAs($other);
    $this->putJson("/api/expenses/{$id}", ['label' => 'hack'])->assertStatus(403);
});
