<?php

namespace App\Http\Controllers\Chat;

use App\Events\MessageDeleted;
use App\Events\MessageReacted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageDeletion;
use App\Models\MessageReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    private function safeBroadcast(object $event, bool $toOthers = true): void
    {
        try {
            $pending = broadcast($event);
            if ($toOthers) {
                $pending->toOthers();
            }
            unset($pending);
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed: '.$e->getMessage());
        }
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);
        $userId = $request->user()->id;

        $before = $request->query('before');
        $query = $conversation->messages()
            ->withTrashed()
            ->visibleTo($userId)
            ->with(['sender:id,name,email,avatar', 'reactions'])
            ->latest()
            ->limit(50);

        if ($before) {
            $query->where('id', '<', $before);
        }

        $messages = $query->get()->reverse()->values();

        $messages->transform(fn ($msg) => $this->formatMessage($msg));

        return response()->json([
            'messages' => $messages,
            'has_more' => $messages->count() === 50,
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);

        if ($conversation->type === 'private') {
            $otherParticipant = $conversation->activeParticipants()
                ->where('user_id', '!=', $request->user()->id)
                ->first();

            if ($otherParticipant) {
                $blocked = BlockedUser::where(function ($q) use ($request, $otherParticipant) {
                    $q->where('user_id', $request->user()->id)
                        ->where('blocked_user_id', $otherParticipant->id);
                })->orWhere(function ($q) use ($request, $otherParticipant) {
                    $q->where('user_id', $otherParticipant->id)
                        ->where('blocked_user_id', $request->user()->id);
                })->exists();

                if ($blocked) {
                    return response()->json(['error' => 'Cannot send messages in this conversation.'], 403);
                }
            }
        }

        $validated = $request->validate([
            'body' => 'nullable|string|max:10000',
            'type' => 'sometimes|in:text,image,video,file,audio',
            'parent_id' => 'sometimes|nullable|exists:messages,id',
            'attachments' => 'sometimes|array|max:10',
            'attachments.*' => 'file|max:20480',
        ]);

        $body = $validated['body'] ?? null;
        $type = $validated['type'] ?? 'text';
        $metadata = null;

        if (! $body && ! $request->hasFile('attachments')) {
            return response()->json(['error' => 'A message body or attachment is required.'], 422);
        }

        if ($request->hasFile('attachments')) {
            $files = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments/'.$conversation->id, 'public');
                $files[] = [
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
            $metadata = ['files' => $files];

            if ($type === 'text' && ! $body) {
                $mime = $files[0]['mime'];
                if (str_starts_with($mime, 'image/')) {
                    $type = 'image';
                } elseif (str_starts_with($mime, 'video/')) {
                    $type = 'video';
                } elseif (str_starts_with($mime, 'audio/')) {
                    $type = 'audio';
                } else {
                    $type = 'file';
                }
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'type' => $type,
            'body' => $body,
            'metadata' => $metadata,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message->load(['sender:id,name,email,avatar', 'reactions']);

        $this->safeBroadcast(new MessageSent($message));

        return response()->json(['message' => $this->formatMessage($message)], 201);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $latestMessage = $conversation->messages()->latest()->first();
        if (! $latestMessage) {
            return response()->json(['ok' => true]);
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_message_id' => $latestMessage->id,
        ]);

        $this->safeBroadcast(new MessageRead(
            $conversation->id,
            $user->id,
            $latestMessage->id,
        ));

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();
        $mode = $request->input('mode', 'for_me');

        if ($mode === 'for_everyone') {
            if ($user->id !== $message->sender_id) {
                return response()->json(['error' => 'Only the sender can delete for everyone.'], 403);
            }

            $conversationId = $message->conversation_id;
            $messageId = $message->id;

            $message->delete();

            $this->safeBroadcast(new MessageDeleted($conversationId, $messageId));
        } else {
            MessageDeletion::firstOrCreate([
                'message_id' => $message->id,
                'user_id' => $user->id,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function react(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:8',
        ]);

        $user = $request->user();
        $conversation = $message->conversation;
        $this->authorizeParticipant($user, $conversation);

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);
        }

        $reactions = $this->getFormattedReactions($message);

        $this->safeBroadcast(new MessageReacted(
            $conversation->id,
            $message->id,
            $reactions,
        ), false);

        return response()->json(['reactions' => $reactions]);
    }

    public function forward(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $user = $request->user();
        $targetConversation = Conversation::findOrFail($validated['conversation_id']);
        $this->authorizeParticipant($user, $targetConversation);

        $forwardedMessage = Message::create([
            'conversation_id' => $targetConversation->id,
            'sender_id' => $user->id,
            'type' => $message->type,
            'body' => $message->body,
            'metadata' => $message->metadata,
        ]);

        $forwardedMessage->load(['sender:id,name,email,avatar', 'reactions']);

        $this->safeBroadcast(new MessageSent($forwardedMessage));

        return response()->json(['message' => $this->formatMessage($forwardedMessage)], 201);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $this->safeBroadcast(new UserTyping(
            $conversation->id,
            $user->id,
            $user->name,
            $request->boolean('is_typing', true),
        ));

        return response()->json(['ok' => true]);
    }

    private function authorizeParticipant($user, Conversation $conversation): void
    {
        $isParticipant = $conversation->activeParticipants()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isParticipant, 403, 'You are not a participant in this conversation.');
    }

    private function getFormattedReactions(Message $message): array
    {
        $reactions = MessageReaction::where('message_id', $message->id)
            ->with('user:id,name')
            ->get();

        return $reactions->groupBy('emoji')->map(fn ($group, $emoji) => [
            'emoji' => $emoji,
            'count' => $group->count(),
            'users' => $group->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => $r->user->name ?? 'Deleted User',
            ])->values()->all(),
        ])->values()->all();
    }

    private function formatMessage(Message $message): array
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
