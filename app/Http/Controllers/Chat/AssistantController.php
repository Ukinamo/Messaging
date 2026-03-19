<?php

namespace App\Http\Controllers\Chat;

use Gemini\Data\Content;
use Gemini\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssistantController
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:24'],
            'messages.*.role' => ['required', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
        ]);

        $apiKey = config('services.gemini.key');
        if (! is_string($apiKey) || $apiKey === '') {
            throw ValidationException::withMessages([
                'gemini' => 'GEMINI_API_KEY is not configured in .env',
            ]);
        }

        $model = (string) config('services.gemini.model') ?: 'gemini-2.0-flash';
        $messages = $data['messages'];

        // Get the last user message as the prompt to answer.
        $lastUserMessage = null;
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? null) === 'user') {
                $lastUserMessage = trim((string) $messages[$i]['content']);
                break;
            }
        }

        if (! is_string($lastUserMessage) || $lastUserMessage === '') {
            return response()->json([
                'error' => 'ai_bad_request',
                'message' => 'No user message found to respond to.',
            ], 400);
        }

        $history = [];
        foreach ($messages as $index => $msg) {
            // Do not include the last user message in history; we'll send it as the next turn.
            if (
                $msg['role'] === 'user'
                && trim((string) $msg['content']) === $lastUserMessage
                && $index === $i
            ) {
                continue;
            }

            $role = $msg['role'];
            $text = trim($msg['content']);

            if ($role === 'system') {
                $history[] = Content::parse(
                    part: "System instruction: {$text}",
                    role: Role::USER
                );
                continue;
            }

            $history[] = Content::parse(
                part: $text,
                role: $role === 'assistant' ? Role::MODEL : Role::USER
            );
        }

        try {
            $client = \Gemini::client($apiKey);
            $modelClient = $client->generativeModel(model: $model);

            if ($history === []) {
                $result = $modelClient->generateContent($lastUserMessage);
            } else {
                $chat = $modelClient->startChat(history: $history);
                $result = $chat->sendMessage($lastUserMessage);
            }

            $content = trim((string) $result->text());
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $isRateLimited = (bool) preg_match('/quota|rate|429|resource_exhausted|retry in/i', $message);
            $status = $isRateLimited ? 429 : 502;

            Log::warning('Gemini SDK error', [
                'status' => $status,
                'message' => $message,
            ]);

            return response()->json([
                'error' => 'ai_request_failed',
                'status' => $status,
                'message' => $message !== '' ? $message : 'AI request failed.',
            ], $status);
        }

        if ($content === '') {
            return response()->json([
                'error' => 'ai_bad_response',
                'message' => 'AI returned an empty or blocked response. Try another message or check the model.',
            ], 502);
        }

        return response()->json([
            'message' => [
                'role' => 'assistant',
                'content' => trim($content),
            ],
        ]);
    }
}
