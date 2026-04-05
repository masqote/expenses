<?php

namespace App\Services;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ExpenseService
{
    public function __construct(
        private ExpenseRepositoryInterface $expenseRepository,
        private QuickInputParser $parser
    ) {}

    public function getForPeriod(int $userId, string $period): Collection
    {
        return $this->expenseRepository->getForUserAndPeriod($userId, $period);
    }

    public function getForDateRange(int $userId, string $from, string $to): Collection
    {
        return $this->expenseRepository->getForUserAndDateRange($userId, $from, $to);
    }

    public function createFromQuickInput(int $userId, string $quickInput, string $period, ?int $categoryId = null): Expense
    {
        $parsed = $this->parser->parse($quickInput);

        return $this->expenseRepository->create([
            'user_id'     => $userId,
            'label'       => $parsed['label'],
            'amount'      => $parsed['amount'],
            'period'      => $period,
            'category_id' => $categoryId,
        ]);
    }

    public function create(int $userId, string $label, float $amount, string $period, ?int $categoryId = null): Expense
    {
        return $this->expenseRepository->create([
            'user_id'     => $userId,
            'label'       => $label,
            'amount'      => $amount,
            'period'      => $period,
            'category_id' => $categoryId,
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        return $this->expenseRepository->update($expense, $data);
    }

    public function delete(Expense $expense): void
    {
        $this->expenseRepository->delete($expense);
    }

    public function assertOwnership(Expense $expense, int $userId): void
    {
        if ($expense->user_id !== $userId) {
            throw new AuthorizationException('You do not own this expense.');
        }
    }
}
