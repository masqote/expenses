<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\AdjustmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    public function __construct(private AdjustmentRepositoryInterface $repo) {}

    public function index(Request $request): JsonResponse
    {
        $from = $request->query('from', date('Y-m-01'));
        $to   = $request->query('to', date('Y-m-d'));
        $adjustments = $this->repo->allForUserAndDateRange($request->user()->id, $from, $to);
        return response()->json($adjustments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'note'   => 'nullable|string|max:255',
            'period' => 'required|date_format:Y-m-d',
        ]);

        $adjustment = $this->repo->create([
            'user_id' => $request->user()->id,
            'amount'  => $data['amount'],
            'note'    => $data['note'] ?? null,
            'period'  => $data['period'],
        ]);

        return response()->json($adjustment, 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->repo->delete($id, $request->user()->id);
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['message' => 'Deleted']);
    }
}
