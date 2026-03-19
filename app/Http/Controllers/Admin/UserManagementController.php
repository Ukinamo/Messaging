<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedUserId = (int) ($request->integer('user_id') ?: 0);

        $users = User::query()
            ->select('id', 'name', 'email', 'is_admin', 'created_at')
            ->orderBy('name')
            ->get();

        $selectedUser = $selectedUserId > 0
            ? User::query()->select('id', 'name', 'email')->find($selectedUserId)
            : null;

        $conversations = collect();
        if ($selectedUser) {
            $conversations = Conversation::forUser($selectedUser->id)
                ->with(['latestMessage.sender:id,name', 'activeParticipants:users.id,users.name,users.avatar'])
                ->get()
                ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
                ->values()
                ->map(function (Conversation $conversation) use ($selectedUser) {
                    $other = $conversation->getOtherParticipant($selectedUser->id);

                    return [
                        'id' => $conversation->id,
                        'type' => $conversation->type,
                        'name' => $conversation->type === 'private'
                            ? ($other?->name ?? 'Deleted User')
                            : ($conversation->name ?? 'Unnamed Group'),
                        'latest_message' => $conversation->latestMessage ? [
                            'body' => $conversation->latestMessage->body,
                            'sender_name' => $conversation->latestMessage->sender?->name ?? 'Deleted User',
                            'created_at' => $conversation->latestMessage->created_at->toISOString(),
                        ] : null,
                    ];
                });
        }

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'selectedUserId' => $selectedUser?->id,
            'selectedUserName' => $selectedUser?->name,
            'selectedUserConversations' => $conversations,
        ]);
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('success', 'User role updated.');
    }
}

