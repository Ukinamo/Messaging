<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $query = $request->input('q');
        $authId = $request->user()->id;

        $blockedIds = BlockedUser::where('user_id', $authId)
            ->pluck('blocked_user_id')
            ->merge(
                BlockedUser::where('blocked_user_id', $authId)->pluck('user_id')
            );

        $users = User::where('id', '!=', $authId)
            ->whereNotIn('id', $blockedIds)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email', 'avatar')
            ->limit(20)
            ->get();

        return response()->json(['users' => $users]);
    }
}
