<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'avatar',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'nickname', 'last_read_message_id', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeParticipants(): BelongsToMany
    {
        return $this->participants()->whereNull('conversation_participants.left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $userId));
    }

    public function getOtherParticipant(int $userId): ?User
    {
        if ($this->type !== 'private') {
            return null;
        }

        $other = $this->activeParticipants->firstWhere('id', '!=', $userId);

        if (!$other && $this->activeParticipants->count() === 1) {
            return $this->activeParticipants->first();
        }

        return $other;
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants->firstWhere('id', $userId);
        $lastReadId = $participant?->pivot?->last_read_message_id ?? 0;

        return $this->messages()
            ->where('id', '>', $lastReadId)
            ->where('sender_id', '!=', $userId)
            ->count();
    }
}
