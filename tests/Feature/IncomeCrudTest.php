<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates an income entry and returns 201', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/income', [
        'label'  => 'freelance',
        'amount' => 500000,
        'period' => '2025-06',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['label' => 'freelance']);
});

it('lists income entries for a period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 100000, 'period' => '2025-06']);
    $this->postJson('/api/income', ['label' => 'side gig', 'amount' => 75000, 'period' => '2025-06']);
    $this->postJson('/api/income', ['label' => 'old', 'amount' => 50000, 'period' => '2025-05']);

    $response = $this->getJson('/api/income?period=2025-06');

    $response->assertStatus(200);
    expect(count($response->json()))->toBe(2);
});

it('updates an income entry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 100000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $response = $this->putJson("/api/income/{$id}", ['label' => 'big bonus', 'amount' => 200000]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['label' => 'big bonus']);
});

it('deletes an income entry and returns 204', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 100000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $this->deleteJson("/api/income/{$id}")->assertStatus(204);
});

it('returns 404 after deleting an income entry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $create = $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 100000, 'period' => '2025-06']);
    $id     = $create->json('id');

    $this->deleteJson("/api/income/{$id}");

    $this->putJson("/api/income/{$id}", ['label' => 'x'])->assertStatus(404);
});

it('returns 403 when updating another user income', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($owner);

    $create = $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 100000, 'period' => '2025-06']);
    $id     = $create->json('id');

    Sanctum::actingAs($other);
    $this->putJson("/api/income/{$id}", ['label' => 'hack'])->assertStatus(403);
});
