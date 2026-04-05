<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\GroupMember;
use App\Repositories\Contracts\GroupRepositoryInterface;

class GroupRepository implements GroupRepositoryInterface
{
    public function create(array $data): Group
    {
        return Group::create($data);
    }

    public function findById(int $id): ?Group
    {
        return Group::find($id);
    }

    public function addMember(int $groupId, int $userId): GroupMember
    {
        return GroupMember::create([
            'group_id' => $groupId,
            'user_id'  => $userId,
        ]);
    }

    public function removeMember(int $groupId, int $userId): void
    {
        GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function isMember(int $groupId, int $userId): bool
    {
        return GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getMemberByUserId(int $userId): ?GroupMember
    {
        return GroupMember::where('user_id', $userId)->first();
    }
}
