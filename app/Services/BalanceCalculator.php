<?php

namespace App\Services;

use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Contracts\IncomeRepositoryInterface;
use App\Repositories\Contracts\SalaryRepositoryInterface;

class BalanceCalculator
{
    public function __construct(
        private SalaryRepositoryInterface $salaryRepo,
        private IncomeRepositoryInterface $incomeRepo,
        private ExpenseRepositoryInterface $expenseRepo
    ) {}

    /**
     * Calculate the balance summary for a user in a given period.
     *
     * @return array{salary: float|null, total_income: float, total_expenses: float, balance: float|null}
     */
    public function calculate(int $userId, string $period): array
    {
        $salary        = $this->salaryRepo->findByUserAndPeriod($userId, $period);
        $totalIncome   = $this->incomeRepo->sumForUserAndPeriod($userId, $period);
        $totalExpenses = $this->expenseRepo->sumForUserAndPeriod($userId, $period);

        $balance = $salary
            ? (float) $salary->amount + $totalIncome - $totalExpenses
            : null;

        return [
            'salary'         => $salary ? (float) $salary->amount : null,
            'total_income'   => $totalIncome,
            'total_expenses' => $totalExpenses,
            'balance'        => $balance,
        ];
    }
}
