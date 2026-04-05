<?php

namespace App\Http\Controllers;

use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(private GroupService $groupService) {}

    public function myGroups(Request $request)
    {
        $user = $request->user();

        // All groups the user owns
        $owned = $user->ownedGroups()->get()
            ->map(fn($g) => [
                'id'           => $g->id,
                'name'         => $g->name,
                'member_count' => $g->members()->count(),
                'is_owner'     => true,
            ]);

        // Group the user is a member of (invited, not owner)
        $memberGroup = null;
        $member = $user->groupMember()->with('group')->first();
        if ($member && $member->group->owner_id !== $user->id) {
            $memberGroup = [
                'id'           => $member->group->id,
                'name'         => $member->group->name,
                'member_count' => null,
                'is_owner'     => false,
            ];
        }

        $groups = $owned->toArray();
        if ($memberGroup) $groups[] = $memberGroup;

        return response()->json($groups);
    }

    public function myGroup(Request $request)
    {
        $user = $request->user();

        // Check if user owns any group
        $ownedGroup = $user->ownedGroups()->first();
        if ($ownedGroup) {
            return response()->json([
                'id'       => $ownedGroup->id,
                'name'     => $ownedGroup->name,
                'owner_id' => $ownedGroup->owner_id,
            ]);
        }

        // Check if user is a member of a group
        $member = $user->groupMember()->with('group')->first();
        if ($member) {
            return response()->json([
                'id'       => $member->group->id,
                'name'     => $member->group->name,
                'owner_id' => $member->group->owner_id,
            ]);
        }

        return response()->json(null);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $group = $this->groupService->createGroup($request->user()->id, $request->input('name'));

        return response()->json($group, 201);
    }

    public function show(Request $request, $group)
    {
        $group = $this->groupService->getGroup((int) $group);

        return response()->json($group);
    }

    public function removeMember(Request $request, $group, $user)
    {
        $group = $this->groupService->getGroup((int) $group);

        $this->groupService->removeMember($group, $request->user()->id, (int) $user);

        return response()->noContent();
    }
}
