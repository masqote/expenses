<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryRequest;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function __construct(private SalaryService $salaryService) {}

    public function show(Request $request)
    {
        // Return all salary entries for the user
        $salaries = $this->salaryService->allForUser($request->user()->id);
        return response()->json($salaries);
    }

    public function store(StoreSalaryRequest $request)
    {
        $period = $request->input('period', date('Y-m-d'));
        // Normalize YYYY-MM to YYYY-MM-01
        if (strlen($period) === 7) $period .= '-01';

        $salary = $this->salaryService->upsert(
            $request->user()->id,
            $period,
            (float) $request->input('amount')
        );

        return response()->json($salary, 201);
    }

    public function destroy(Request $request, string $period)
    {
        $this->salaryService->delete($request->user()->id, $period);
        return response()->noContent();
    }
}
