<?php

namespace App\Http\Controllers;

use App\Services\GroupExpenseService;
use Illuminate\Http\Request;

class GroupSummaryController extends Controller
{
    public function __construct(private GroupExpenseService $groupExpenseService) {}

    public function show(Request $request, $group)
    {
        $from    = $request->query('from', date('Y-m-01'));
        $to      = $request->query('to', date('Y-m-d'));
        $summary = $this->groupExpenseService->getGroupSummaryRange((int) $group, $from, $to);

        return response()->json(array_merge(['from' => $from, 'to' => $to], $summary));
    }
}
