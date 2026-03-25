<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscrowTransaction extends Model
{
    protected $fillable = [
        'escrow_id', 'job_id', 'type', 'amount',
        'payment_method', 'payment_reference', 'description',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function escrow() { return $this->belongsTo(Escrow::class); }

    private function camelType(): string
    {
        return lcfirst(str_replace('_', '', ucwords($this->type, '_')));
    }

    public function toApiArray(): array
    {
        return [
            'id'               => (string) $this->id,
            'escrowId'         => (string) $this->escrow_id,
            'jobId'            => (string) $this->job_id,
            'type'             => $this->camelType(),
            'amount'           => (float) $this->amount,
            'paymentMethod'    => $this->payment_method,
            'paymentReference' => $this->payment_reference,
            'description'      => $this->description,
            'createdAt'        => $this->created_at?->toIso8601String(),
        ];
    }
}
