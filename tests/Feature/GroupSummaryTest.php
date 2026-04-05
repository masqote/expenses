<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('calculates group summary with multiple members, one missing salary', function () {
    $owner  = User::factory()->create();
    $member = User::factory()->create();

    // Create group and add member
    Sanctum::actingAs($owner);
    $groupId = $this->postJson('/api/groups', ['name' => 'Household'])->json('id');
    $inviteId = $this->postJson("/api/groups/{$groupId}/invites", ['email' => $member->email])->json('id');

    Sanctum::actingAs($member);
    $this->postJson("/api/invites/{$inviteId}/accept");

    // Owner sets salary and adds expenses
    Sanctum::actingAs($owner);
    $this->postJson('/api/salary', ['amount' => 5000000, 'period' => '2025-06']);
    $this->postJson('/api/expenses', ['label' => 'rent', 'amount' => 1000000, 'period' => '2025-06']);
    $this->postJson('/api/income', ['label' => 'bonus', 'amount' => 500000, 'period' => '2025-06']);

    // Member adds expenses but NO salary
    Sanctum::actingAs($member);
    $this->postJson('/api/expenses', ['label' => 'food', 'amount' => 300000, 'period' => '2025-06']);

    // Get group summary
    Sanctum::actingAs($owner);
    $response = $this->getJson("/api/groups/{$groupId}/summary?period=2025-06");
    $response->assertStatus(200);

    $data    = $response->json();
    $members = $data['members'];

    // Find owner and member summaries
    $ownerSummary  = collect($members)->firstWhere('user_id', $owner->id);
    $memberSummary = collect($members)->firstWhere('user_id', $member->id);

    // Owner: salary=5000000, income=500000, expenses=1000000, balance=4500000
    expect((float) $ownerSummary['salary'])->toBe(5000000.0);
    expect((float) $ownerSummary['total_income'])->toBe(500000.0);
    expect((float) $ownerSummary['total_expenses'])->toBe(1000000.0);
    expect((float) $ownerSummary['balance'])->toBe(4500000.0);

    // Member: no salary → balance null
    expect($memberSummary['salary'])->toBeNull();
    expect($memberSummary['balance'])->toBeNull();

    // Group balance excludes member with null balance
    expect((float) $data['group_balance'])->toBe(4500000.0);
});
