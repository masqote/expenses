<?php

namespace App\Http\Controllers;

use App\Services\GroupExpenseService;
use Illuminate\Http\Request;

class GroupExpenseController extends Controller
{
    public function __construct(private GroupExpenseService $groupExpenseService) {}

    public function index(Request $request, $group)
    {
        $period   = $request->query('period', date('Y-m'));
        $expenses = $this->groupExpenseService->getGroupExpenses((int) $group, $period);

        return response()->json($expenses);
    }
}
