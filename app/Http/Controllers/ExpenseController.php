<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService) {}

    public function index(Request $request)
    {
        $from = $request->query('from', date('Y-m-01'));
        $to   = $request->query('to', date('Y-m-d'));
        $expenses = $this->expenseService->getForDateRange($request->user()->id, $from, $to);

        return response()->json($expenses);
    }

    public function store(StoreExpenseRequest $request)
    {
        $user   = $request->user();
        $period = $request->input('period', date('Y-m-d'));

        if ($request->filled('quick_input')) {
            $parsed = $this->expenseService->createFromQuickInput(
                $user->id,
                $request->input('quick_input'),
                $period,
                $request->input('category_id') ? (int) $request->input('category_id') : null
            );

            if (isset($parsed['type']) && $parsed['type'] !== 'expense') {
                throw ValidationException::withMessages([
                    'quick_input' => 'Quick input must be an expense (e.g. "label : amount").',
                ]);
            }

            return response()->json($parsed, 201);
        }

        $expense = $this->expenseService->create(
            $user->id,
            $request->input('label'),
            (float) $request->input('amount'),
            $period,
            $request->input('category_id') ? (int) $request->input('category_id') : null
        );

        return response()->json($expense, 201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $this->expenseService->assertOwnership($expense, $request->user()->id);

        $updated = $this->expenseService->update($expense, $request->only(['label', 'amount', 'category_id']));

        return response()->json($updated);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->expenseService->assertOwnership($expense, $request->user()->id);

        $this->expenseService->delete($expense);

        return response()->noContent();
    }
}
