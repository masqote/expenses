<?php

namespace App\Http\Controllers;

use App\Services\GroupExpenseService;
use Illuminate\Http\Request;

class GroupSummaryController extends Controller
{
    public function __construct(private GroupExpenseService $groupExpenseService) {}

    public function show(Request $request, $group)
    {
        $period  = $request->query('period', date('Y-m'));
        $summary = $this->groupExpenseService->getGroupSummary((int) $group, $period);

        return response()->json(array_merge(['period' => $period], $summary));
    }
}
