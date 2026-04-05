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

    public function upsert(int $userId, string $period, float $amount): Salary
    {
        return $this->salaryRepository->upsert($userId, $period, $amount);
    }
}
