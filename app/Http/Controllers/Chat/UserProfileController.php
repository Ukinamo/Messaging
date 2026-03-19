<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function api(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at->toISOString(),
            'is_blocked' => $authUser->hasBlocked($user->id),
            'is_blocked_by_them' => $user->hasBlocked($authUser->id),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $authUser = $request->user();

        $isBlocked = $authUser->hasBlocked($user->id);
        $isBlockedByThem = $user->hasBlocked($authUser->id);

        $sharedConversationId = null;
        $existing = Conversation::where('type', 'private')
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $authUser->id))
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))
            ->first();

        if ($existing) {
            $sharedConversationId = $existing->id;
        }

        return Inertia::render('Chat/UserProfile', [
            'profileUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at->toISOString(),
            ],
            'isBlocked' => $isBlocked,
            'isBlockedByThem' => $isBlockedByThem,
            'sharedConversationId' => $sharedConversationId,
        ]);
    }
}
