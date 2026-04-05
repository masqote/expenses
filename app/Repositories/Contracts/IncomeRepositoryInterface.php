<?php

namespace App\Repositories\Contracts;

use App\Models\Income;
use Illuminate\Support\Collection;

interface IncomeRepositoryInterface
{
    public function getForUserAndPeriod(int $userId, string $period): Collection;

    public function create(array $data): Income;

    public function findById(int $id): ?Income;

    public function update(Income $income, array $data): Income;

    public function delete(Income $income): void;

    public function sumForUserAndPeriod(int $userId, string $period): float;
}
