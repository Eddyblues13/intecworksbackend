<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    protected $fillable = [
        'service_job_id', 'participant_a', 'participant_b',
        'last_message', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function participantA() { return $this->belongsTo(User::class, 'participant_a'); }
    public function participantB() { return $this->belongsTo(User::class, 'participant_b'); }
    public function serviceJob()   { return $this->belongsTo(ServiceJob::class); }
    public function messages()     { return $this->hasMany(ChatMessage::class); }

    /**
     * Check if a user is a participant.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participant_a === $userId || $this->participant_b === $userId;
    }

    /**
     * Get the other participant relative to $userId.
     */
    public function otherParticipant(int $userId): ?User
    {
        $otherId = $this->participant_a === $userId
            ? $this->participant_b
            : $this->participant_a;
        return User::find($otherId);
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function toApiArray(int $currentUserId): array
    {
        $other = $this->otherParticipant($currentUserId);
        $job = $this->serviceJob;

        // Determine type based on participant roles
        $type = 'client_artisan';
        if ($other && $other->role === 'supplier') {
            $type = 'client_supplier';
        }

        return [
            'id'               => (string) $this->id,
            'jobId'            => $this->service_job_id ? (string) $this->service_job_id : '',
            'serviceJobId'     => $this->service_job_id ? (string) $this->service_job_id : null,
            'jobDescription'   => $job?->description,
            'participants'     => [(string) $this->participant_a, (string) $this->participant_b],
            'type'             => $type,
            'participantName'  => $other?->full_name,
            'participantAvatar'=> $other?->avatar_url,
            'participantRole'  => $other?->role,
            'otherUser'        => $other ? [
                'id'       => (string) $other->id,
                'fullName' => $other->full_name,
                'avatarUrl'=> $other->avatar_url,
                'role'     => $other->role,
            ] : null,
            'lastMessage'      => $this->last_message,
            'lastMessageAt'    => $this->last_message_at?->toIso8601String(),
            'unreadCount'      => $this->unreadCountFor($currentUserId),
            'isFlagged'        => false,
            'flagReason'       => null,
            'createdAt'        => $this->created_at?->toIso8601String(),
        ];
    }
}
