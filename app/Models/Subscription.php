<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_name',
        'amount',
        'currency',
        'status',
        'payment_reference',
        'starts_at',
        'expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'starts_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isActive() && $this->expires_at->diffInDays(Carbon::now()) <= $days;
    }

    /**
     * Available subscription plans.
     */
    public static function plans(): array
    {
        return [
            'basic_monthly' => [
                'id'       => 'basic_monthly',
                'name'     => 'Basic Monthly',
                'amount'   => 5000.00,
                'currency' => 'NGN',
                'duration' => 30,
            ],
            'basic_quarterly' => [
                'id'       => 'basic_quarterly',
                'name'     => 'Basic Quarterly',
                'amount'   => 12000.00,
                'currency' => 'NGN',
                'duration' => 90,
            ],
            'basic_annual' => [
                'id'       => 'basic_annual',
                'name'     => 'Basic Annual',
                'amount'   => 40000.00,
                'currency' => 'NGN',
                'duration' => 365,
            ],
            'pro_monthly' => [
                'id'       => 'pro_monthly',
                'name'     => 'Pro Monthly',
                'amount'   => 15000.00,
                'currency' => 'NGN',
                'duration' => 30,
            ],
            'pro_annual' => [
                'id'       => 'pro_annual',
                'name'     => 'Pro Annual',
                'amount'   => 120000.00,
                'currency' => 'NGN',
                'duration' => 365,
            ],
        ];
    }

    /**
     * API-friendly representation.
     */
    public function toApiArray(): array
    {
        return [
            'id'               => (string) $this->id,
            'planId'           => $this->plan_id,
            'planName'         => $this->plan_name,
            'amount'           => (float) $this->amount,
            'currency'         => $this->currency,
            'status'           => $this->status,
            'paymentReference' => $this->payment_reference,
            'startsAt'         => $this->starts_at?->toIso8601String(),
            'expiresAt'        => $this->expires_at?->toIso8601String(),
            'cancelledAt'      => $this->cancelled_at?->toIso8601String(),
            'createdAt'        => $this->created_at?->toIso8601String(),
        ];
    }
}
