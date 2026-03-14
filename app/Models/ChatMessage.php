<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_thread_id', 'sender_id', 'content', 'type',
        'attachments', 'is_read', 'is_flagged',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read'     => 'boolean',
        'is_flagged'  => 'boolean',
    ];

    public function thread() { return $this->belongsTo(ChatThread::class, 'chat_thread_id'); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }

    public function toApiArray(): array
    {
        return [
            'id'          => (string) $this->id,
            'threadId'    => (string) $this->chat_thread_id,
            'senderId'    => (string) $this->sender_id,
            'senderName'  => $this->sender?->full_name,
            'content'     => $this->content,
            'type'        => $this->type,
            'attachments' => $this->attachments ?? [],
            'isRead'      => $this->is_read,
            'isFlagged'   => $this->is_flagged,
            'createdAt'   => $this->created_at?->toIso8601String(),
        ];
    }
}
