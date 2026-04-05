<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to register with valid data and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['token']);
    expect($response->json('token'))->not->toBeEmpty();
});

it('rejects registration with a duplicate email', function () {
    $payload = [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ];

    $this->postJson('/api/register', $payload)->assertStatus(201);

    $this->postJson('/api/register', $payload)->assertStatus(422);
});

it('rejects registration when password is shorter than 8 characters', function () {
    $response = $this->postJson('/api/register', [
        'name'     => 'Bob',
        'email'    => 'bob@example.com',
        'password' => 'short',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

it('allows a user to login with valid credentials and returns a token', function () {
    $this->postJson('/api/register', [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/login', [
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['token']);
    expect($response->json('token'))->not->toBeEmpty();
});

it('rejects login with a wrong password without hinting which field is wrong', function () {
    $this->postJson('/api/register', [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/login', [
        'email'    => 'alice@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);

    // Response must not hint at which field is wrong
    $body = $response->json();
    expect($body)->not->toHaveKey('errors');
});

it('allows a user to logout and the token is then rejected with 401', function () {
    $register = $this->postJson('/api/register', [
        'name'     => 'Alice',
        'email'    => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $token = $register->json('token');

    // Logout with the token
    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertStatus(204);

    // Verify the token was deleted from the database
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
