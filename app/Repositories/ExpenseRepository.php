<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use Illuminate\Support\Collection;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function getForUserAndPeriod(int $userId, string $period): Collection
    {
        return Expense::with('category')
            ->where('user_id', $userId)
            ->where('period', $period)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function findById(int $id): ?Expense
    {
        return Expense::find($id);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh();
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    public function sumForUserAndPeriod(int $userId, string $period): float
    {
        return (float) Expense::where('user_id', $userId)
            ->where('period', $period)
            ->sum('amount');
    }

    public function getForGroupAndPeriod(array $userIds, string $period): Collection
    {
        return Expense::with(['user', 'category'])
            ->whereIn('user_id', $userIds)
            ->where('period', $period)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($e) => array_merge($e->toArray(), ['user_name' => $e->user->name]));
    }
}
