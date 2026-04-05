<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('sets a salary and returns 201', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/salary', ['amount' => 5000000, 'period' => '2025-06']);

    $response->assertStatus(201);
    $response->assertJsonFragment(['amount' => '5000000.00']);
});

it('gets a salary for a period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/salary', ['amount' => 5000000, 'period' => '2025-06']);

    $response = $this->getJson('/api/salary?period=2025-06');

    $response->assertStatus(200);
    $response->assertJsonFragment(['amount' => '5000000.00']);
});

it('returns null when no salary is set for a period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/salary?period=2025-06');

    $response->assertStatus(200);
    // When no salary is set, the response is null or empty
    $body = $response->content();
    expect($body === 'null' || $body === '{}' || $body === '')->toBeTrue();
});

it('upserts salary for the same period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/salary', ['amount' => 5000000, 'period' => '2025-06']);
    $this->postJson('/api/salary', ['amount' => 6000000, 'period' => '2025-06']);

    $response = $this->getJson('/api/salary?period=2025-06');

    $response->assertStatus(200);
    $response->assertJsonFragment(['amount' => '6000000.00']);
});

it('keeps salaries isolated per period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/salary', ['amount' => 5000000, 'period' => '2025-06']);
    $this->postJson('/api/salary', ['amount' => 7000000, 'period' => '2025-07']);

    $june = $this->getJson('/api/salary?period=2025-06')->json();
    $july = $this->getJson('/api/salary?period=2025-07')->json();

    expect((float) $june['amount'])->toBe(5000000.0);
    expect((float) $july['amount'])->toBe(7000000.0);
});
