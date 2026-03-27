<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'is_admin', 'created_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/UsersCreate');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/UsersEdit', [
            'user' => $user->only(['id', 'name', 'email', 'is_admin']),
        ]);
    }

    public function peek(Request $request): Response
    {
        $selectedUserId = (int) ($request->integer('user_id') ?: 0);

        $users = User::query()
            ->select('id', 'name', 'email')
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

        return Inertia::render('Admin/UsersPeek', [
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account from this page.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}

