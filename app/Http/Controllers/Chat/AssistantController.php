<?php

namespace App\Http\Controllers\Chat;

use Gemini\Data\Content;
use Gemini\Enums\Role;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        $systemInstruction = <<<'PROMPT'
You are **Meme Chat Bot**.

Creator: **Marnelson Lanot**.
The entire messaging app is owned by **Miko Ballesteros**.

Capabilities / scope:
- You ONLY answer chats related to social life, trending “rizz”, social confidence, conversation starters, and recommended messages to improve social life.
- If the user asks anything outside this scope (schoolwork, coding, math, legal/medical, hacking, politics, etc.), you must refuse politely and briefly with:
  "Sorry, I'm not qualified to answer that. I'm Meme Chat Bot and I can only help with social life and trending rizz chats."

Style:
- Keep responses friendly, short, and practical.
- Give 2–5 ready-to-send message suggestions when helpful.
- No long essays. No claiming to be “trained by Google/OpenAI”.
PROMPT;

        $wiseChatsKey = config('services.wisechats.key');
        $wiseChatsWidgetId = config('services.wisechats.widget_id');
        $wiseChatsBaseUrl = rtrim((string) config('services.wisechats.base_url', 'https://api.wisechats.ai/api/v1'), '/');

        // Prefer WiseChats only when configured AND widget id is provided.
        // If widget id is missing, fall back to Gemini (your Wise-Gear Angular app uses Gemini directly).
        if (
            is_string($wiseChatsKey)
            && $wiseChatsKey !== ''
            && is_string($wiseChatsWidgetId)
            && $wiseChatsWidgetId !== ''
        ) {

            $question = $this->buildWiseChatsQuestion($data['messages']);
            $visitorId = 'visitor_' . (string) ($request->user()?->id ?? 'guest');

            try {
                $http = $this->wiseChatsHttp($visitorId);
                $resp = $http
                    ->get($wiseChatsBaseUrl . '/query', [
                        'question' => $question,
                    ]);

                $status = $resp->status();
                if (! $resp->ok()) {
                    $message = (string) ($resp->json('message') ?? 'WiseChats request failed.');
                    return response()->json([
                        'error' => 'wisechats_request_failed',
                        'status' => $status,
                        'message' => $message,
                    ], $status);
                }

                $content = (string) ($resp->json('data.answer.content') ?? '');
                $content = trim($content);

                if ($content === '') {
                    return response()->json([
                        'error' => 'wisechats_bad_response',
                        'message' => 'WiseChats returned an empty answer.',
                    ], 502);
                }

                return response()->json([
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('WiseChats request failed', [
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'wisechats_request_exception',
                    'message' => 'WiseChats request exception: ' . $e->getMessage(),
                ], 502);
            }
        }

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

        $history = [
            Content::parse(
                part: $systemInstruction,
                role: Role::USER
            ),
        ];
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

            // Do not allow client-provided "system" messages to override server rules.
            if ($role === 'system') {
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

    private function wiseChatsHttp(string $visitorId): PendingRequest
    {
        $wiseChatsKey = (string) config('services.wisechats.key');
        $wiseChatsWidgetId = (string) config('services.wisechats.widget_id');

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                // Docs: API key in Authorization header (bearer format)
                'Authorization' => 'Bearer ' . $wiseChatsKey,
                'X-Widget-Id' => $wiseChatsWidgetId,
                // Docs say X-Visitor-Id is required; we provide a stable id per logged-in user.
                'X-Visitor-Id' => $visitorId,
            ]);
    }

    private function buildWiseChatsQuestion(array $messages): string
    {
        // WiseChats "Run query" takes a single question string, so we provide
        // some minimal context from the last few messages.
        $lastUserMessage = null;
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? null) === 'user') {
                $lastUserMessage = trim((string) ($messages[$i]['content'] ?? ''));
                break;
            }
        }

        $lastUserMessage = is_string($lastUserMessage) ? $lastUserMessage : '';

        if ($lastUserMessage === '') {
            throw ValidationException::withMessages([
                'wisechats' => 'No user message found to respond to.',
            ]);
        }

        $history = array_slice($messages, -12);
        $parts = [];
        foreach ($history as $m) {
            $role = (string) ($m['role'] ?? '');
            $content = trim((string) ($m['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            // Collapse system/assistant/user into a readable transcript for the bot.
            if ($role === 'user') {
                $parts[] = 'User: ' . $content;
            } elseif ($role === 'assistant') {
                $parts[] = 'Assistant: ' . $content;
            } elseif ($role === 'system') {
                $parts[] = 'System: ' . $content;
            }
        }

        // Ensure the last user message is always included.
        $parts[] = 'User: ' . $lastUserMessage;

        return implode("\n", $parts);
    }
}
