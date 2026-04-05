<?php

namespace App\Repositories;

use App\Models\Invite;
use App\Repositories\Contracts\InviteRepositoryInterface;
use Illuminate\Support\Collection;

class InviteRepository implements InviteRepositoryInterface
{
    public function create(array $data): Invite
    {
        return Invite::create($data);
    }

    public function findById(int $id): ?Invite
    {
        return Invite::find($id);
    }

    public function findPendingForUser(int $userId): Collection
    {
        return Invite::where('invitee_id', $userId)
            ->where('status', 'pending')
            ->get();
    }

    public function updateStatus(Invite $invite, string $status): Invite
    {
        $invite->update(['status' => $status]);
        return $invite->fresh();
    }
}
