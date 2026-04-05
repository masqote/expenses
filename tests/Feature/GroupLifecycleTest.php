<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('completes full group lifecycle: create → invite → accept → view → remove → 403', function () {
    $owner  = User::factory()->create();
    $member = User::factory()->create();

    // Create group
    Sanctum::actingAs($owner);
    $groupRes = $this->postJson('/api/groups', ['name' => 'Test Group']);
    $groupRes->assertStatus(201);
    $groupId = $groupRes->json('id');

    // Send invite
    $inviteRes = $this->postJson("/api/groups/{$groupId}/invites", ['email' => $member->email]);
    $inviteRes->assertStatus(201);
    $inviteId = $inviteRes->json('id');

    // Accept invite
    Sanctum::actingAs($member);
    $this->postJson("/api/invites/{$inviteId}/accept")->assertStatus(200);

    // Member can view group expenses
    $this->getJson("/api/groups/{$groupId}/expenses")->assertStatus(200);

    // Owner removes member
    Sanctum::actingAs($owner);
    $this->deleteJson("/api/groups/{$groupId}/members/{$member->id}")->assertStatus(204);

    // Removed member gets 403
    Sanctum::actingAs($member);
    $this->getJson("/api/groups/{$groupId}/expenses")->assertStatus(403);
});

it('declines an invite and does not add member', function () {
    $owner  = User::factory()->create();
    $member = User::factory()->create();

    Sanctum::actingAs($owner);
    $groupRes = $this->postJson('/api/groups', ['name' => 'Test Group']);
    $groupId  = $groupRes->json('id');

    $inviteRes = $this->postJson("/api/groups/{$groupId}/invites", ['email' => $member->email]);
    $inviteId  = $inviteRes->json('id');

    Sanctum::actingAs($member);
    $this->postJson("/api/invites/{$inviteId}/decline")->assertStatus(200);

    // Member should not have access
    $this->getJson("/api/groups/{$groupId}/expenses")->assertStatus(403);
});

it('enforces one group per user', function () {
    $owner1 = User::factory()->create();
    $owner2 = User::factory()->create();
    $member = User::factory()->create();

    // Create two groups and invite member to both
    Sanctum::actingAs($owner1);
    $group1 = $this->postJson('/api/groups', ['name' => 'Group 1'])->json('id');
    $invite1 = $this->postJson("/api/groups/{$group1}/invites", ['email' => $member->email])->json('id');

    Sanctum::actingAs($owner2);
    $group2 = $this->postJson('/api/groups', ['name' => 'Group 2'])->json('id');
    $invite2 = $this->postJson("/api/groups/{$group2}/invites", ['email' => $member->email])->json('id');

    // Accept first invite
    Sanctum::actingAs($member);
    $this->postJson("/api/invites/{$invite1}/accept")->assertStatus(200);

    // Accepting second invite should fail with 422
    $this->postJson("/api/invites/{$invite2}/accept")->assertStatus(422);
});
