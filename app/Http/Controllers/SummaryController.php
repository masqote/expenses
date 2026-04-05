<?php

namespace App\Http\Controllers;

use App\Services\BalanceCalculator;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(private BalanceCalculator $calculator) {}

    public function show(Request $request)
    {
        $today = date('Y-m-d');
        $from  = $request->query('from', date('Y-m-01'));
        $to    = $request->query('to', $today);
        $summary = $this->calculator->calculateRange($request->user()->id, $from, $to);

        return response()->json(array_merge(['from' => $from, 'to' => $to], $summary));
    }
}
