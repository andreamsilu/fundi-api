<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fundi_id',
        'job_id',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns the chat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the fundi that owns the chat.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the job associated with the chat.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the messages for the chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the unread messages count for a specific user.
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->count();
    }
} 