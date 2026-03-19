<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ArchivedConversation;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Chat/Index', [
            'conversations' => $this->getConversationsForUser($request->user()),
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $this->authorizeParticipant($request->user(), $conversation);
        $peekUserId = (int) $request->integer('peek_user_id');
        $peekUser = null;
        $viewerUserId = $request->user()->id;

        if ($request->user()->isAdmin() && $peekUserId > 0) {
            $participant = $conversation->activeParticipants()
                ->select('users.id', 'users.name', 'users.email', 'users.avatar')
                ->where('users.id', $peekUserId)
                ->first();

            $peekTarget = $participant ?: User::query()
                ->select('id', 'name', 'email', 'avatar')
                ->find($peekUserId);

            if ($peekTarget) {
                $peekUser = [
                    'id' => $peekTarget->id,
                    'name' => $peekTarget->name,
                    'email' => $peekTarget->email,
                    'avatar' => $peekTarget->avatar,
                ];
                $viewerUserId = $peekTarget->id;
            }
        }

        $messages = $conversation->messages()
            ->withTrashed()
            ->visibleTo($request->user()->id)
            ->with(['sender:id,name,email,avatar', 'reactions.user:id,name'])
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($msg) => $this->formatMessageWithReactions($msg));

        $participants = $conversation->activeParticipants()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->get();

        $displayName = $conversation->name;
        $displayAvatar = $conversation->avatar;

        if ($conversation->type === 'private') {
            $other = $participants->firstWhere('id', '!=', $request->user()->id)
                ?? $participants->firstWhere('id', $request->user()->id);
            $isSelfChat = $other && $other->id === $request->user()->id;
            $displayName = $isSelfChat ? ($other->name . ' (You)') : ($other?->name ?? 'Deleted User');
            $displayAvatar = $other?->avatar;
        }

        $lastReadByOthers = $conversation->participants()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNotNull('conversation_participants.last_read_message_id')
            ->max('conversation_participants.last_read_message_id') ?? 0;

        $isArchived = ArchivedConversation::where('user_id', $request->user()->id)
            ->where('conversation_id', $conversation->id)
            ->exists();

        $isBlocked = false;
        if ($conversation->type === 'private' && !($isSelfChat ?? false)) {
            $otherForBlock = $participants->firstWhere('id', '!=', $request->user()->id);
            if ($otherForBlock) {
                $isBlocked = $request->user()->hasBlocked($otherForBlock->id);
            }
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $this->getConversationsForUser($request->user()),
            'viewerUserId' => $viewerUserId,
            'peekMode' => [
                'enabled' => $peekUser !== null,
                'user' => $peekUser,
            ],
            'activeConversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $displayName,
                'avatar' => $displayAvatar,
                'participants' => $participants,
                'messages' => $messages,
                'last_read_by_others' => (int) $lastReadByOthers,
                'is_archived' => $isArchived,
                'is_blocked' => $isBlocked,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $authUser = $request->user();
        $targetUserId = (int) $validated['user_id'];

        $isSelfChat = $authUser->id === $targetUserId;

        if ($isSelfChat) {
            $candidates = Conversation::where('type', 'private')
                ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $authUser->id))
                ->withCount('activeParticipants')
                ->get()
                ->where('active_participants_count', 1);

            $archivedIds = ArchivedConversation::where('user_id', $authUser->id)->pluck('conversation_id');
            $existing = $candidates->whereNotIn('id', $archivedIds)->first()
                ?? $candidates->first();
        } else {
            $existing = Conversation::where('type', 'private')
                ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $authUser->id))
                ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $targetUserId))
                ->first();
        }

        if ($existing) {
            ArchivedConversation::where('user_id', $authUser->id)
                ->where('conversation_id', $existing->id)
                ->delete();

            return response()->json(['conversation_id' => $existing->id]);
        }

        $conversation = Conversation::create(['type' => 'private']);

        if ($isSelfChat) {
            $conversation->participants()->attach([
                $authUser->id => ['role' => 'member', 'joined_at' => now()],
            ]);
        } else {
            $conversation->participants()->attach([
                $authUser->id => ['role' => 'member', 'joined_at' => now()],
                $targetUserId => ['role' => 'member', 'joined_at' => now()],
            ]);
        }

        return response()->json(['conversation_id' => $conversation->id], 201);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $authUser = $request->user();
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $validated['name'],
        ]);

        $participantIds = collect($validated['user_ids'])
            ->push($authUser->id)
            ->unique()
            ->mapWithKeys(fn ($id) => [
                $id => [
                    'role' => $id === $authUser->id ? 'owner' : 'member',
                    'joined_at' => now(),
                ],
            ]);

        $conversation->participants()->attach($participantIds);

        return response()->json(['conversation_id' => $conversation->id], 201);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        return response()->json([
            'conversations' => $this->getConversationsForUser($request->user()),
        ]);
    }

    private function getConversationsForUser(User $user): array
    {
        $archivedIds = ArchivedConversation::where('user_id', $user->id)
            ->pluck('conversation_id');

        $conversations = Conversation::forUser($user->id)
            ->whereNotIn('conversations.id', $archivedIds)
            ->with([
                'latestMessage.sender:id,name',
                'activeParticipants:users.id,users.name,users.email,users.avatar',
            ])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();

        return $conversations->map(function ($conversation) use ($user) {
            $other = $conversation->getOtherParticipant($user->id);
            $isSelfChat = $other && $other->id === $user->id;
            $participant = $conversation->activeParticipants->firstWhere('id', $user->id);
            $lastReadId = $participant?->pivot?->last_read_message_id ?? 0;

            $unreadCount = $conversation->messages()
                ->where('id', '>', $lastReadId)
                ->where('sender_id', '!=', $user->id)
                ->count();

            $privateName = $isSelfChat
                ? ($other->name . ' (You)')
                : ($other?->name ?? 'Deleted User');

            return [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $conversation->type === 'private'
                    ? $privateName
                    : $conversation->name,
                'avatar' => $conversation->type === 'private'
                    ? $other?->avatar
                    : $conversation->avatar,
                'participants' => $conversation->activeParticipants->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'avatar' => $p->avatar,
                ]),
                'latest_message' => $conversation->latestMessage ? [
                    'id' => $conversation->latestMessage->id,
                    'body' => $conversation->latestMessage->body,
                    'type' => $conversation->latestMessage->type,
                    'sender_name' => $conversation->latestMessage->sender?->name ?? 'Deleted User',
                    'created_at' => $conversation->latestMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })->toArray();
    }

    public function archivedIndex(Request $request): Response
    {
        $user = $request->user();

        $archivedIds = ArchivedConversation::where('user_id', $user->id)
            ->pluck('conversation_id');

        $conversations = Conversation::whereIn('id', $archivedIds)
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'latestMessage.sender:id,name',
                'activeParticipants:users.id,users.name,users.email,users.avatar',
            ])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();

        $formatted = $conversations->map(function ($conversation) use ($user) {
            $other = $conversation->getOtherParticipant($user->id);
            $isSelfChat = $other && $other->id === $user->id;

            $privateName = $isSelfChat
                ? ($other->name . ' (You)')
                : ($other?->name ?? 'Deleted User');

            return [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $conversation->type === 'private'
                    ? $privateName
                    : $conversation->name,
                'avatar' => $conversation->type === 'private'
                    ? $other?->avatar
                    : $conversation->avatar,
                'latest_message' => $conversation->latestMessage ? [
                    'id' => $conversation->latestMessage->id,
                    'body' => $conversation->latestMessage->body,
                    'type' => $conversation->latestMessage->type,
                    'sender_name' => $conversation->latestMessage->sender?->name ?? 'Deleted User',
                    'created_at' => $conversation->latestMessage->created_at->toISOString(),
                ] : null,
            ];
        })->toArray();

        return Inertia::render('Chat/Archived', [
            'archivedConversations' => $formatted,
        ]);
    }

    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);

        ArchivedConversation::firstOrCreate([
            'user_id' => $request->user()->id,
            'conversation_id' => $conversation->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function restore(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);

        ArchivedConversation::where('user_id', $request->user()->id)
            ->where('conversation_id', $conversation->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeParticipant(User $user, Conversation $conversation): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $isParticipant = $conversation->activeParticipants()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isParticipant, 403, 'You are not a participant in this conversation.');
    }

    private function formatMessageWithReactions($message): array
    {
        $isDeleted = $message->trashed();

        $reactions = $isDeleted ? [] : $message->reactions->groupBy('emoji')->map(fn ($group, $emoji) => [
            'emoji' => $emoji,
            'count' => $group->count(),
            'users' => $group->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => $r->user->name ?? 'Deleted User',
            ])->values()->all(),
        ])->values()->all();

        $sender = $message->sender;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->name,
                'avatar' => $sender->avatar ?? null,
            ] : [
                'id' => 0,
                'name' => 'Deleted User',
                'avatar' => null,
            ],
            'type' => $message->type,
            'body' => $isDeleted ? null : $message->body,
            'metadata' => $isDeleted ? null : $message->metadata,
            'parent_id' => $message->parent_id,
            'created_at' => $message->created_at->toISOString(),
            'reactions' => $reactions,
            'deleted_for_everyone' => $isDeleted,
        ];
    }
}
