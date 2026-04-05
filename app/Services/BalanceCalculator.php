<?php

namespace App\Services;

use App\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Repositories\Contracts\IncomeRepositoryInterface;
use App\Repositories\Contracts\SalaryRepositoryInterface;

class BalanceCalculator
{
    public function __construct(
        private SalaryRepositoryInterface $salaryRepo,
        private IncomeRepositoryInterface $incomeRepo,
        private ExpenseRepositoryInterface $expenseRepo,
        private AdjustmentRepositoryInterface $adjustmentRepo
    ) {}

    /**
     * Calculate summary for a YYYY-MM period (used by salary lookup).
     */
    public function calculate(int $userId, string $period): array
    {
        if (strlen($period) === 7) $period .= '-01';
        // For group summary using a month period, find salary within that month
        $monthEnd = date('Y-m-t', strtotime($period));
        $salary        = $this->salaryRepo->findByUserAndDateRange($userId, $period, $monthEnd);
        $totalIncome   = $this->incomeRepo->sumForUserAndPeriod($userId, $period);
        $totalExpenses = $this->expenseRepo->sumForUserAndPeriod($userId, $period);
        $totalAdjustments = $this->adjustmentRepo->sumForUserAndPeriod($userId, $period);

        $salaryAmount = $salary ? (float) $salary->amount : 0;
        $balance = $salaryAmount + $totalIncome - $totalExpenses + $totalAdjustments;

        return [
            'salary'             => $salary ? (float) $salary->amount : null,
            'total_income'       => $totalIncome,
            'total_expenses'     => $totalExpenses,
            'total_adjustments'  => $totalAdjustments,
            'balance'            => $balance,
        ];
    }

    /**
     * Calculate summary for a date range (from/to YYYY-MM-DD).
     * Salary is looked up by the month of the 'from' date.
     */
    public function calculateRange(int $userId, string $from, string $to): array
    {
        $month         = substr($from, 0, 7) . '-01'; // YYYY-MM-01
        $salary        = $this->salaryRepo->findByUserAndDateRange($userId, $from, $to);
        $totalIncome   = $this->incomeRepo->sumForUserAndDateRange($userId, $from, $to);
        $totalExpenses = $this->expenseRepo->sumForUserAndDateRange($userId, $from, $to);
        $totalAdjustments = $this->adjustmentRepo->sumForUserAndDateRange($userId, $from, $to);

        $salaryAmount = $salary ? (float) $salary->amount : 0;
        $balance = $salaryAmount + $totalIncome - $totalExpenses + $totalAdjustments;

        return [
            'salary'             => $salary ? (float) $salary->amount : null,
            'total_income'       => $totalIncome,
            'total_expenses'     => $totalExpenses,
            'total_adjustments'  => $totalAdjustments,
            'balance'            => $balance,
        ];
    }
}
