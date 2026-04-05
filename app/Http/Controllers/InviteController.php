<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Services\GroupService;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function __construct(private GroupService $groupService) {}

    public function index(Request $request)
    {
        $invites = $this->groupService->getPendingInvites($request->user()->id);

        return response()->json($invites);
    }

    public function store(Request $request, $group)
    {
        $request->validate(['email' => ['required', 'email']]);

        $groupModel = $this->groupService->getGroup((int) $group);
        $invite     = $this->groupService->sendInvite($groupModel, $request->user()->id, $request->input('email'));

        return response()->json($invite, 201);
    }

    public function accept(Request $request, Invite $invite)
    {
        $member = $this->groupService->acceptInvite($invite, $request->user()->id);

        return response()->json($member);
    }

    public function decline(Request $request, Invite $invite)
    {
        $invite = $this->groupService->declineInvite($invite, $request->user()->id);

        return response()->json($invite);
    }
}
