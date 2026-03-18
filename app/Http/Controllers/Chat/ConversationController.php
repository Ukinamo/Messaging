<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
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

        $messages = $conversation->messages()
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
            $other = $participants->firstWhere('id', '!=', $request->user()->id);
            $displayName = $other?->name ?? 'Deleted User';
            $displayAvatar = $other?->avatar;
        }

        $lastReadByOthers = $conversation->participants()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNotNull('conversation_participants.last_read_message_id')
            ->max('conversation_participants.last_read_message_id') ?? 0;

        return Inertia::render('Chat/Index', [
            'conversations' => $this->getConversationsForUser($request->user()),
            'activeConversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $displayName,
                'avatar' => $displayAvatar,
                'participants' => $participants,
                'messages' => $messages,
                'last_read_by_others' => (int) $lastReadByOthers,
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

        if ($authUser->id === $targetUserId) {
            return response()->json(['error' => 'Cannot create conversation with yourself.'], 422);
        }

        $existing = Conversation::where('type', 'private')
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $authUser->id))
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $targetUserId))
            ->first();

        if ($existing) {
            return response()->json(['conversation_id' => $existing->id]);
        }

        $conversation = Conversation::create(['type' => 'private']);
        $conversation->participants()->attach([
            $authUser->id => ['role' => 'member', 'joined_at' => now()],
            $targetUserId => ['role' => 'member', 'joined_at' => now()],
        ]);

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
        $conversations = Conversation::forUser($user->id)
            ->with([
                'latestMessage.sender:id,name',
                'activeParticipants:users.id,users.name,users.email,users.avatar',
            ])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();

        return $conversations->map(function ($conversation) use ($user) {
            $other = $conversation->getOtherParticipant($user->id);
            $participant = $conversation->activeParticipants->firstWhere('id', $user->id);
            $lastReadId = $participant?->pivot?->last_read_message_id ?? 0;

            $unreadCount = $conversation->messages()
                ->where('id', '>', $lastReadId)
                ->where('sender_id', '!=', $user->id)
                ->count();

            return [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $conversation->type === 'private'
                    ? $other?->name ?? 'Deleted User'
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
                    'sender_name' => $conversation->latestMessage->sender?->name,
                    'created_at' => $conversation->latestMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })->toArray();
    }

    private function authorizeParticipant(User $user, Conversation $conversation): void
    {
        $isParticipant = $conversation->activeParticipants()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isParticipant, 403, 'You are not a participant in this conversation.');
    }

    private function formatMessageWithReactions($message): array
    {
        $reactions = $message->reactions->groupBy('emoji')->map(fn ($group, $emoji) => [
            'emoji' => $emoji,
            'count' => $group->count(),
            'users' => $group->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => $r->user->name ?? 'Unknown',
            ])->values()->all(),
        ])->values()->all();

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
                'avatar' => $message->sender->avatar ?? null,
            ] : null,
            'type' => $message->type,
            'body' => $message->body,
            'metadata' => $message->metadata,
            'parent_id' => $message->parent_id,
            'created_at' => $message->created_at->toISOString(),
            'reactions' => $reactions,
        ];
    }
}
