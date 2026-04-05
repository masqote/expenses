<?php

namespace App\Services;

use App\Models\Income;
use App\Repositories\Contracts\IncomeRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class IncomeService
{
    public function __construct(
        private IncomeRepositoryInterface $incomeRepository,
        private QuickInputParser $parser
    ) {}

    public function getForPeriod(int $userId, string $period): Collection
    {
        return $this->incomeRepository->getForUserAndPeriod($userId, $period);
    }

    public function getForDateRange(int $userId, string $from, string $to): Collection
    {
        return $this->incomeRepository->getForUserAndDateRange($userId, $from, $to);
    }

    public function createFromQuickInput(int $userId, string $quickInput, string $period): Income
    {
        $parsed = $this->parser->parse($quickInput);

        return $this->incomeRepository->create([
            'user_id' => $userId,
            'label'   => $parsed['label'],
            'amount'  => $parsed['amount'],
            'period'  => $period,
        ]);
    }

    public function create(int $userId, string $label, float $amount, string $period): Income
    {
        return $this->incomeRepository->create([
            'user_id' => $userId,
            'label'   => $label,
            'amount'  => $amount,
            'period'  => $period,
        ]);
    }

    public function update(Income $income, array $data): Income
    {
        return $this->incomeRepository->update($income, $data);
    }

    public function delete(Income $income): void
    {
        $this->incomeRepository->delete($income);
    }

    public function assertOwnership(Income $income, int $userId): void
    {
        if ($income->user_id !== $userId) {
            throw new AuthorizationException('You do not own this income entry.');
        }
    }
}
