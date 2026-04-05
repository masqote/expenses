<?php

namespace App\Repositories\Contracts;

use App\Models\Expense;
use Illuminate\Support\Collection;

interface ExpenseRepositoryInterface
{
    public function getForUserAndPeriod(int $userId, string $period): Collection;

    public function create(array $data): Expense;

    public function findById(int $id): ?Expense;

    public function update(Expense $expense, array $data): Expense;

    public function delete(Expense $expense): void;

    public function sumForUserAndPeriod(int $userId, string $period): float;

    public function getForGroupAndPeriod(array $userIds, string $period): Collection;
}
