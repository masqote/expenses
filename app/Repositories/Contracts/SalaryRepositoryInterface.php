<?php

namespace App\Repositories\Contracts;

use App\Models\Salary;

interface SalaryRepositoryInterface
{
    public function findByUserAndPeriod(int $userId, string $period): ?Salary;

    public function upsert(int $userId, string $period, float $amount): Salary;
}
