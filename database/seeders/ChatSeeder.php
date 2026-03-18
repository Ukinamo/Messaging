<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect();

        for ($i = 1; $i <= 5; $i++) {
            $users->push(User::firstOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'name' => fake()->name(),
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            ));
        }

        $mainUser = $users->first();

        for ($i = 1; $i < $users->count(); $i++) {
            $other = $users[$i];

            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->attach([
                $mainUser->id => ['role' => 'member', 'joined_at' => now()],
                $other->id => ['role' => 'member', 'joined_at' => now()],
            ]);

            $messageCount = rand(5, 15);
            for ($j = 0; $j < $messageCount; $j++) {
                $sender = rand(0, 1) === 0 ? $mainUser : $other;
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'type' => 'text',
                    'body' => fake()->sentence(rand(3, 12)),
                    'created_at' => now()->subMinutes($messageCount - $j),
                ]);
            }
        }

        $groupConversation = Conversation::create([
            'type' => 'group',
            'name' => 'Team Chat',
        ]);

        $participantData = $users->mapWithKeys(fn ($user, $idx) => [
            $user->id => [
                'role' => $idx === 0 ? 'owner' : 'member',
                'joined_at' => now(),
            ],
        ]);

        $groupConversation->participants()->attach($participantData);

        for ($j = 0; $j < 20; $j++) {
            $sender = $users->random();
            Message::create([
                'conversation_id' => $groupConversation->id,
                'sender_id' => $sender->id,
                'type' => 'text',
                'body' => fake()->sentence(rand(3, 15)),
                'created_at' => now()->subMinutes(20 - $j),
            ]);
        }

        $this->command->info('Chat seeder completed: '.$users->count().' users, 5 conversations created.');
        $this->command->info('Login as: user1@example.com / password');
    }
}
