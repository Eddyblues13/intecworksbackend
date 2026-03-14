<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
        'location',
        'lat',
        'lng',
        'avatar_url',
        'account_status',
        'rejection_reason',
        'trust_score',
        'skill_badge',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'lat' => 'float',
            'lng' => 'float',
            'trust_score' => 'float',
        ];
    }

    // ── Relationships ──

    public function otpVerifications()
    {
        return $this->hasMany(OtpVerification::class);
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function clientJobs()
    {
        return $this->hasMany(ServiceJob::class, 'client_id');
    }

    public function artisanJobs()
    {
        return $this->hasMany(ServiceJob::class, 'artisan_id');
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'artisan_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'payer_id');
    }

    // ── Helpers ──

    public function isProvider(): bool
    {
        return in_array($this->role, ['artisan', 'supplier']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function adminActivityLogs()
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id');
    }

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
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
            'role'          => $this->role,
            'avatarUrl'     => $this->avatar_url,
            'location'      => $this->location,
            'lat'           => $this->lat,
            'lng'           => $this->lng,
            'accountStatus' => $this->flutter_account_status,
            'createdAt'     => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Map DB snake_case account_status to Flutter camelCase enum.
     */
    public function getFlutterAccountStatusAttribute(): string
    {
        return match ($this->account_status) {
            'otp_pending'              => 'otpPending',
            'verification_pending'     => 'verificationPending',
            'verification_under_review'=> 'verificationUnderReview',
            'subscription_required'    => 'subscriptionRequired',
            default                    => $this->account_status,
        };
    }
}
