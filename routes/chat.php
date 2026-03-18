<?php

use App\Http\Controllers\Chat\BlockController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\UserProfileController;
use App\Http\Controllers\Chat\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::get('/chat/archived', [ConversationController::class, 'archivedIndex'])->name('chat.archived');
    Route::get('/chat/blocked', [BlockController::class, 'index'])->name('chat.blocked');
    Route::get('/chat/user/{user}', [UserProfileController::class, 'show'])->name('chat.user.profile');
    Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');

    Route::prefix('api/chat')->group(function () {
        Route::get('/conversations', [ConversationController::class, 'apiIndex'])->name('api.chat.conversations');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('api.chat.conversations.store');
        Route::post('/conversations/group', [ConversationController::class, 'storeGroup'])->name('api.chat.conversations.store-group');

        Route::post('/conversations/{conversation}/archive', [ConversationController::class, 'archive'])->name('api.chat.conversations.archive');
        Route::delete('/conversations/{conversation}/archive', [ConversationController::class, 'restore'])->name('api.chat.conversations.restore');

        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('api.chat.messages.index');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('api.chat.messages.store');
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'markAsRead'])->name('api.chat.messages.read');
        Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing'])->name('api.chat.typing');

        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('api.chat.messages.destroy');
        Route::post('/messages/{message}/react', [MessageController::class, 'react'])->name('api.chat.messages.react');
        Route::post('/messages/{message}/forward', [MessageController::class, 'forward'])->name('api.chat.messages.forward');

        Route::post('/block/{user}', [BlockController::class, 'store'])->name('api.chat.block.store');
        Route::delete('/block/{user}', [BlockController::class, 'destroy'])->name('api.chat.block.destroy');

        Route::get('/users/search', UserSearchController::class)->name('api.chat.users.search');
    });
});
