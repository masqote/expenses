<?php

namespace App\Repositories\Contracts;

use App\Models\Adjustment;
use Illuminate\Support\Collection;

interface AdjustmentRepositoryInterface
{
    public function allForUserAndPeriod(int $userId, string $period): Collection;
    public function allForUserAndDateRange(int $userId, string $from, string $to): Collection;
    public function create(array $data): Adjustment;
    public function delete(int $id, int $userId): bool;
    public function sumForUserAndPeriod(int $userId, string $period): float;
    public function sumForUserAndDateRange(int $userId, string $from, string $to): float;
}
