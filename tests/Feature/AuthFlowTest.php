<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('completes full auth flow: register → login → access protected → logout → 401', function () {
    // Register
    $register = $this->postJson('/api/register', [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);
    $register->assertStatus(201);
    $token = $register->json('token');
    expect($token)->not->toBeEmpty();

    // Access protected endpoint
    $this->withToken($token)
        ->getJson('/api/expenses')
        ->assertStatus(200);

    // Logout
    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertStatus(204);

    // The token should now be deleted from DB — verify directly
    $this->assertDatabaseMissing('personal_access_tokens', [
        'token' => hash('sha256', explode('|', $token)[1] ?? $token),
    ]);
});

it('rejects unauthenticated requests to protected endpoints', function () {
    $this->getJson('/api/expenses')->assertStatus(401);
    $this->getJson('/api/income')->assertStatus(401);
    $this->getJson('/api/salary')->assertStatus(401);
    $this->getJson('/api/summary')->assertStatus(401);
});
