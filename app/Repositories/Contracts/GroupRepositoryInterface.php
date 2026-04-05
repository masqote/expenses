<?php

namespace App\Repositories\Contracts;

use App\Models\Group;
use App\Models\GroupMember;

interface GroupRepositoryInterface
{
    public function create(array $data): Group;

    public function findById(int $id): ?Group;

    public function addMember(int $groupId, int $userId): GroupMember;

    public function removeMember(int $groupId, int $userId): void;

    public function isMember(int $groupId, int $userId): bool;

    public function getMemberByUserId(int $userId): ?GroupMember;
}
