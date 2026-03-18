<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlockController extends Controller
{
    public function index(Request $request): Response
    {
        $blocked = BlockedUser::where('user_id', $request->user()->id)
            ->with('blockedUser:id,name,email,avatar')
            ->latest()
            ->get()
            ->map(fn (BlockedUser $b) => [
                'id' => $b->id,
                'user' => $b->blockedUser ? [
                    'id' => $b->blockedUser->id,
                    'name' => $b->blockedUser->name,
                    'email' => $b->blockedUser->email,
                    'avatar' => $b->blockedUser->avatar,
                ] : null,
                'created_at' => $b->created_at->toISOString(),
            ])
            ->filter(fn ($b) => $b['user'] !== null)
            ->values();

        return Inertia::render('Chat/Blocked', [
            'blockedUsers' => $blocked,
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return response()->json(['error' => 'Cannot block yourself.'], 422);
        }

        BlockedUser::firstOrCreate([
            'user_id' => $authUser->id,
            'blocked_user_id' => $user->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        BlockedUser::where('user_id', $request->user()->id)
            ->where('blocked_user_id', $user->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
