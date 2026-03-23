<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Chat\ConversationController;
use App\Models\ArchivedConversation;
use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ConversationController $conversations): Response
    {
        $user = $request->user();
        $list = $conversations->getConversationsForUser($user);

        $totalConversations = count($list);
        $unreadMessages = collect($list)->sum('unread_count');
        $archivedCount = ArchivedConversation::where('user_id', $user->id)->count();
        $blockedCount = BlockedUser::where('user_id', $user->id)->count();

        $recentConversations = array_slice($list, 0, 5);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalConversations' => $totalConversations,
                'unreadMessages' => $unreadMessages,
                'archivedCount' => $archivedCount,
                'blockedCount' => $blockedCount,
            ],
            'recentConversations' => $recentConversations,
        ]);
    }
}
