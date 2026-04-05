<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncomeController extends Controller
{
    public function __construct(private IncomeService $incomeService) {}

    public function index(Request $request)
    {
        $period  = $request->query('period', date('Y-m'));
        $incomes = $this->incomeService->getForPeriod($request->user()->id, $period);

        return response()->json($incomes);
    }

    public function store(StoreIncomeRequest $request)
    {
        $user   = $request->user();
        $period = $request->input('period', date('Y-m'));

        if ($request->filled('quick_input')) {
            $income = $this->incomeService->createFromQuickInput(
                $user->id,
                $request->input('quick_input'),
                $period
            );

            return response()->json($income, 201);
        }

        $income = $this->incomeService->create(
            $user->id,
            $request->input('label'),
            (float) $request->input('amount'),
            $period
        );

        return response()->json($income, 201);
    }

    public function update(UpdateIncomeRequest $request, Income $income)
    {
        $this->incomeService->assertOwnership($income, $request->user()->id);

        $updated = $this->incomeService->update($income, $request->only(['label', 'amount']));

        return response()->json($updated);
    }

    public function destroy(Request $request, Income $income)
    {
        $this->incomeService->assertOwnership($income, $request->user()->id);

        $this->incomeService->delete($income);

        return response()->noContent();
    }
}
