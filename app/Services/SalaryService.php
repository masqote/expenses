<?php

namespace App\Services;

use App\Models\Salary;
use App\Repositories\Contracts\SalaryRepositoryInterface;

class SalaryService
{
    public function __construct(
        private SalaryRepositoryInterface $salaryRepository
    ) {}

    public function getForPeriod(int $userId, string $period): ?Salary
    {
        return $this->salaryRepository->findByUserAndPeriod($userId, $period);
    }

    public function getForDateRange(int $userId, string $from, string $to): ?Salary
    {
        return $this->salaryRepository->findByUserAndDateRange($userId, $from, $to);
    }

    public function allForUser(int $userId): \Illuminate\Support\Collection
    {
        return $this->salaryRepository->allForUser($userId);
    }

    public function upsert(int $userId, string $period, float $amount): Salary
    {
        return $this->salaryRepository->upsert($userId, $period, $amount);
    }

    public function delete(int $userId, string $period): bool
    {
        return Salary::where('user_id', $userId)->where('period', $period)->delete() > 0;
    }
}
