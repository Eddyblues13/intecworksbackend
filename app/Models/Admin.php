<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'avatar_url',
        'location',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ──

    public function activityLogs()
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id');
    }

    public function broadcastNotifications()
    {
        return $this->hasMany(BroadcastNotification::class, 'admin_id');
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Convert to the JSON shape the Flutter client expects.
     */
    public function toApiArray(): array
    {
        return [
            'id'            => (string) $this->id,
            'fullName'      => $this->full_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'role'          => 'admin',
            'avatarUrl'     => $this->avatar_url,
            'location'      => $this->location,
            'accountStatus' => $this->status,
            'createdAt'     => $this->created_at?->toIso8601String(),
        ];
    }
}
