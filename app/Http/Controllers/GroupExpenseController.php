<?php

namespace App\Http\Controllers;

use App\Services\GroupExpenseService;
use Illuminate\Http\Request;

class GroupExpenseController extends Controller
{
    public function __construct(private GroupExpenseService $groupExpenseService) {}

    public function index(Request $request, $group)
    {
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));
        $expenses = $this->groupExpenseService->getGroupExpensesRange((int) $group, $from, $to);

        return response()->json($expenses);
    }
}
