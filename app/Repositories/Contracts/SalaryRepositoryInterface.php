<?php

namespace App\Repositories\Contracts;

use App\Models\Salary;

interface SalaryRepositoryInterface
{
    public function findByUserAndPeriod(int $userId, string $period): ?Salary;
    public function findByUserAndDateRange(int $userId, string $from, string $to): ?Salary;
    public function upsert(int $userId, string $period, float $amount): Salary;
    public function allForUser(int $userId): \Illuminate\Support\Collection;
}
