<?php

namespace App\Http\Controllers\Chat;

use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\CallRejected;
use App\Events\CallSignal;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function initiate(Request $request, Conversation $conversation): JsonResponse
    {
        $authUser = $request->user();

        abort_unless(
            $conversation->activeParticipants()->where('user_id', $authUser->id)->exists(),
            403,
            'You are not a participant in this conversation.',
        );

        abort_unless($conversation->type === 'private', 422, 'Calls are only supported in private conversations.');

        $receiver = $conversation->activeParticipants()
            ->where('user_id', '!=', $authUser->id)
            ->first();

        abort_unless($receiver, 422, 'No other participant found.');

        $activeCall = Call::where('conversation_id', $conversation->id)
            ->whereIn('status', ['initiated', 'ringing', 'active'])
            ->first();

        if ($activeCall) {
            return response()->json(['error' => 'A call is already in progress.'], 409);
        }

        $call = Call::create([
            'conversation_id' => $conversation->id,
            'caller_id' => $authUser->id,
            'receiver_id' => $receiver->id,
            'status' => 'ringing',
        ]);

        broadcast(new CallInitiated($call))->toOthers();

        $call->load(['caller', 'receiver']);

        return response()->json([
            'call' => [
                'id' => $call->id,
                'conversation_id' => $call->conversation_id,
                'caller' => [
                    'id' => $call->caller->id,
                    'name' => $call->caller->name,
                    'avatar' => $call->caller->avatar,
                ],
                'receiver' => [
                    'id' => $call->receiver->id,
                    'name' => $call->receiver->name,
                    'avatar' => $call->receiver->avatar,
                ],
                'status' => $call->status,
            ],
        ], 201);
    }

    public function answer(Request $request, Call $call): JsonResponse
    {
        $authUser = $request->user();

        abort_unless($call->receiver_id === $authUser->id, 403, 'Only the receiver can answer.');
        abort_unless($call->status === 'ringing', 422, 'Call is not ringing.');

        $call->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        broadcast(new CallAnswered($call))->toOthers();

        return response()->json(['status' => 'active']);
    }

    public function reject(Request $request, Call $call): JsonResponse
    {
        $authUser = $request->user();

        abort_unless($call->isParticipant($authUser->id), 403);
        abort_unless(in_array($call->status, ['initiated', 'ringing']), 422, 'Call cannot be rejected.');

        $call->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);

        broadcast(new CallRejected($call))->toOthers();

        return response()->json(['status' => 'rejected']);
    }

    public function end(Request $request, Call $call): JsonResponse
    {
        $authUser = $request->user();

        abort_unless($call->isParticipant($authUser->id), 403);
        abort_unless(in_array($call->status, ['initiated', 'ringing', 'active']), 422, 'Call is already ended.');

        $status = $call->status === 'active' ? 'ended' : 'missed';

        $call->update([
            'status' => $status,
            'ended_at' => now(),
        ]);

        broadcast(new CallEnded($call))->toOthers();

        return response()->json(['status' => $status]);
    }

    public function signal(Request $request, Call $call): JsonResponse
    {
        $authUser = $request->user();

        abort_unless($call->isParticipant($authUser->id), 403);
        abort_unless(in_array($call->status, ['ringing', 'active']), 422, 'Call is not active.');

        $validated = $request->validate([
            'type' => 'required|string|in:offer,answer,ice-candidate',
            'payload' => 'required|array',
        ]);

        broadcast(new CallSignal(
            callId: $call->id,
            fromUserId: $authUser->id,
            type: $validated['type'],
            payload: $validated['payload'],
        ))->toOthers();

        return response()->json(['ok' => true]);
    }
}
