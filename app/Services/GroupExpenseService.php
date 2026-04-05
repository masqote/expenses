<?php

namespace App\Services;

use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Support\Collection;

class GroupExpenseService
{
    public function __construct(
        private GroupRepositoryInterface $groupRepo,
        private ExpenseRepositoryInterface $expenseRepo,
        private BalanceCalculator $calculator
    ) {}

    public function getGroupExpenses(int $groupId, string $period): Collection
    {
        $memberUserIds = $this->getMemberUserIds($groupId);

        return $this->expenseRepo->getForGroupAndPeriod($memberUserIds, $period);
    }

    public function getGroupSummary(int $groupId, string $period): array
    {
        $group   = $this->groupRepo->findById($groupId);
        $members = $group->members()->with('user')->get();

        // Build user list: owner first, then members
        $users = collect();
        $owner = $group->owner;
        if ($owner) {
            $users->push($owner);
        }
        foreach ($members as $member) {
            // Don't duplicate if owner is also in group_members
            if ($member->user_id !== $group->owner_id) {
                $users->push($member->user);
            }
        }

        $memberSummaries = [];
        $groupBalance    = null;

        foreach ($users as $user) {
            $summary = $this->calculator->calculate($user->id, $period);

            $memberSummaries[] = [
                'user_id'        => $user->id,
                'name'           => $user->name,
                'salary'         => $summary['salary'],
                'total_income'   => $summary['total_income'],
                'total_expenses' => $summary['total_expenses'],
                'balance'        => $summary['balance'],
            ];

            if ($summary['balance'] !== null) {
                $groupBalance = ($groupBalance ?? 0) + $summary['balance'];
            }
        }

        return [
            'members'       => $memberSummaries,
            'group_balance' => $groupBalance,
        ];
    }

    private function getMemberUserIds(int $groupId): array
    {
        $group = $this->groupRepo->findById($groupId);
        $memberIds = $group->members()->pluck('user_id')->toArray();

        // Include owner
        if ($group->owner_id && !in_array($group->owner_id, $memberIds)) {
            $memberIds[] = $group->owner_id;
        }

        return $memberIds;
    }
}
