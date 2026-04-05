<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\GroupRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $groupId = (int) $request->route('group');
        $userId  = $request->user()->id;

        /** @var GroupRepositoryInterface $groupRepo */
        $groupRepo = app(GroupRepositoryInterface::class);

        $group = $groupRepo->findById($groupId);

        // Allow if user is the owner OR a member
        if (! $group || (! $groupRepo->isMember($groupId, $userId) && $group->owner_id !== $userId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
