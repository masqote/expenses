<?php

namespace App\Repositories;

use App\Models\Salary;
use App\Repositories\Contracts\SalaryRepositoryInterface;

class SalaryRepository implements SalaryRepositoryInterface
{
    public function findByUserAndPeriod(int $userId, string $period): ?Salary
    {
        return Salary::where('user_id', $userId)
            ->where('period', $period)
            ->first();
    }

    public function upsert(int $userId, string $period, float $amount): Salary
    {
        return Salary::updateOrCreate(
            ['user_id' => $userId, 'period' => $period],
            ['amount' => $amount]
        );
    }
}
