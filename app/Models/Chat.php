<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';
    protected $guarded = ['id'];

    /**
     * Relasi ke model ChatParticipant.
     *
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'chat_id');
    }

    /**
     * Relasi ke model ChatMessage.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }

    /**
     * Relasi ke pesan terakhir (ChatMessage).
     *
     * @return HasOne
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'chat_id')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Scope untuk chat yang memiliki partisipan tertentu.
     *
     * @param $query
     * @param int $userId
     * @return mixed
     */
    public function scopeHasParticipants($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
