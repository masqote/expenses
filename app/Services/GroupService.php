<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Invite;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\InviteRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupService
{
    public function __construct(
        private GroupRepositoryInterface $groupRepo,
        private InviteRepositoryInterface $inviteRepo,
        private UserRepositoryInterface $userRepo
    ) {}

    public function createGroup(int $ownerId, string $name): Group
    {
        return $this->groupRepo->create([
            'name'     => $name,
            'owner_id' => $ownerId,
        ]);
        // Note: owner is NOT added to group_members — they access via owner_id
        // group_members is only for invited members
    }

    public function getGroup(int $id): Group
    {
        $group = $this->groupRepo->findById($id);

        if (! $group) {
            abort(404, 'Group not found.');
        }

        return $group;
    }

    public function removeMember(Group $group, int $requesterId, int $targetUserId): void
    {
        if ($group->owner_id !== $requesterId) {
            throw new AuthorizationException('Only the group owner can remove members.');
        }

        $this->groupRepo->removeMember($group->id, $targetUserId);
    }

    public function sendInvite(Group $group, int $requesterId, string $email): Invite
    {
        if ($group->owner_id !== $requesterId) {
            throw new AuthorizationException('Only the group owner can send invites.');
        }

        $invitee = $this->userRepo->findByEmail($email);

        if (! $invitee) {
            abort(404, 'User with that email not found.');
        }

        if ($this->groupRepo->isMember($group->id, $invitee->id)) {
            throw ValidationException::withMessages([
                'email' => 'This user is already a member of the group.',
            ]);
        }

        return $this->inviteRepo->create([
            'group_id'   => $group->id,
            'invitee_id' => $invitee->id,
            'status'     => 'pending',
        ]);
    }

    public function acceptInvite(Invite $invite, int $userId): GroupMember
    {
        if ($invite->invitee_id !== $userId) {
            throw new AuthorizationException('You are not the invitee for this invite.');
        }

        // Enforce one-group-per-user
        if ($this->groupRepo->getMemberByUserId($userId)) {
            throw ValidationException::withMessages([
                'invite' => 'You are already a member of a group.',
            ]);
        }

        $this->inviteRepo->updateStatus($invite, 'accepted');

        return $this->groupRepo->addMember($invite->group_id, $userId);
    }

    public function declineInvite(Invite $invite, int $userId): Invite
    {
        if ($invite->invitee_id !== $userId) {
            throw new AuthorizationException('You are not the invitee for this invite.');
        }

        return $this->inviteRepo->updateStatus($invite, 'declined');
    }

    public function getPendingInvites(int $userId): Collection
    {
        return $this->inviteRepo->findPendingForUser($userId);
    }
}
