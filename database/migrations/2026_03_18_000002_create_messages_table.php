<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['text', 'image', 'video', 'file', 'audio', 'system'])->default('text');
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->foreign('last_read_message_id')->references('id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropForeign(['last_read_message_id']);
        });

        Schema::dropIfExists('messages');
    }
};
