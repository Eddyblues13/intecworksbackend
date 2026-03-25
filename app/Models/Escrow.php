<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    protected $fillable = [
        'job_id', 'client_id', 'status',
        'deposit_amount', 'remaining_amount', 'material_amount',
        'total_funded', 'total_released', 'total_refunded',
        'fully_funded_at', 'released_at',
    ];

    protected $casts = [
        'deposit_amount'   => 'float',
        'remaining_amount' => 'float',
        'material_amount'  => 'float',
        'total_funded'     => 'float',
        'total_released'   => 'float',
        'total_refunded'   => 'float',
        'fully_funded_at'  => 'datetime',
        'released_at'      => 'datetime',
    ];

    public function job()          { return $this->belongsTo(ServiceJob::class, 'job_id'); }
    public function client()       { return $this->belongsTo(User::class, 'client_id'); }
    public function transactions() { return $this->hasMany(EscrowTransaction::class)->orderByDesc('created_at'); }

    public function isFullyFunded(): bool
    {
        return $this->total_funded >= ($this->deposit_amount + $this->remaining_amount);
    }

    public function outstandingAmount(): float
    {
        return max(0, ($this->deposit_amount + $this->remaining_amount) - $this->total_funded);
    }

    /**
     * Convert snake_case DB status to camelCase for Flutter enum.
     */
    private function camelStatus(): string
    {
        return lcfirst(str_replace('_', '', ucwords($this->status, '_')));
    }

    public function toApiArray(): array
    {
        return [
            'id'              => (string) $this->id,
            'jobId'           => (string) $this->job_id,
            'clientId'        => (string) $this->client_id,
            'status'          => $this->camelStatus(),
            'depositAmount'   => (float) $this->deposit_amount,
            'remainingAmount' => (float) $this->remaining_amount,
            'materialAmount'  => (float) $this->material_amount,
            'totalFunded'     => (float) $this->total_funded,
            'totalReleased'   => (float) $this->total_released,
            'totalRefunded'   => (float) $this->total_refunded,
            'createdAt'       => $this->created_at?->toIso8601String(),
            'fullyFundedAt'   => $this->fully_funded_at?->toIso8601String(),
            'releasedAt'      => $this->released_at?->toIso8601String(),
        ];
    }
}
