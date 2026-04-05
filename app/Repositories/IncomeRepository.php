<?php

namespace App\Repositories;

use App\Models\Income;
use App\Repositories\Contracts\IncomeRepositoryInterface;
use Illuminate\Support\Collection;

class IncomeRepository implements IncomeRepositoryInterface
{
    public function getForUserAndPeriod(int $userId, string $period): Collection
    {
        return Income::where('user_id', $userId)
            ->where('period', $period)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Income
    {
        return Income::create($data);
    }

    public function findById(int $id): ?Income
    {
        return Income::find($id);
    }

    public function update(Income $income, array $data): Income
    {
        $income->update($data);
        return $income->fresh();
    }

    public function delete(Income $income): void
    {
        $income->delete();
    }

    public function sumForUserAndPeriod(int $userId, string $period): float
    {
        return (float) Income::where('user_id', $userId)
            ->where('period', $period)
            ->sum('amount');
    }
}
