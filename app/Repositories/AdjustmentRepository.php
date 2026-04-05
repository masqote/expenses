<?php

namespace App\Repositories;

use App\Models\Adjustment;
use App\Repositories\Contracts\AdjustmentRepositoryInterface;
use Illuminate\Support\Collection;

class AdjustmentRepository implements AdjustmentRepositoryInterface
{
    public function allForUserAndPeriod(int $userId, string $period): Collection
    {
        return Adjustment::where('user_id', $userId)
            ->where('period', 'like', $period . '%')
            ->orderByDesc('period')
            ->orderByDesc('created_at')
            ->get();
    }

    public function allForUserAndDateRange(int $userId, string $from, string $to): Collection
    {
        return Adjustment::where('user_id', $userId)
            ->whereBetween('period', [$from, $to])
            ->orderByDesc('period')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Adjustment
    {
        return Adjustment::create($data);
    }

    public function delete(int $id, int $userId): bool
    {
        return Adjustment::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function sumForUserAndPeriod(int $userId, string $period): float
    {
        return (float) Adjustment::where('user_id', $userId)
            ->where('period', 'like', $period . '%')
            ->sum('amount');
    }

    public function sumForUserAndDateRange(int $userId, string $from, string $to): float
    {
        return (float) Adjustment::where('user_id', $userId)
            ->whereBetween('period', [$from, $to])
            ->sum('amount');
    }
}
