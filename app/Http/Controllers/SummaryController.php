<?php

namespace App\Http\Controllers;

use App\Services\BalanceCalculator;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(private BalanceCalculator $calculator) {}

    public function show(Request $request)
    {
        $period  = $request->query('period', date('Y-m'));
        $summary = $this->calculator->calculate($request->user()->id, $period);

        return response()->json(array_merge(['period' => $period], $summary));
    }
}
