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
        $period = $request->query('period', date('Y-m'));
        $salary = $this->salaryService->getForPeriod($request->user()->id, $period);

        return response()->json($salary);
    }

    public function store(StoreSalaryRequest $request)
    {
        $period = $request->input('period', date('Y-m'));
        $salary = $this->salaryService->upsert(
            $request->user()->id,
            $period,
            (float) $request->input('amount')
        );

        return response()->json($salary, 201);
    }
}
