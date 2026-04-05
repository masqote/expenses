<?php

namespace App\Repositories\Contracts;

use App\Models\Invite;
use Illuminate\Support\Collection;

interface InviteRepositoryInterface
{
    public function create(array $data): Invite;

    public function findById(int $id): ?Invite;

    public function findPendingForUser(int $userId): Collection;

    public function updateStatus(Invite $invite, string $status): Invite;
}
