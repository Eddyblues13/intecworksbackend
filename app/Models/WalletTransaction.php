<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id', 'service_job_id', 'type', 'amount',
        'balance_after', 'status', 'reference', 'description',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];

    public function wallet()     { return $this->belongsTo(Wallet::class); }
    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }

    public function toApiArray(): array
    {
        return [
            'id'           => $this->id,
            'serviceJobId' => $this->service_job_id ? (string) $this->service_job_id : null,
            'type'         => $this->type,
            'amount'       => $this->amount,
            'balanceAfter' => $this->balance_after,
            'status'       => $this->status,
            'reference'    => $this->reference,
            'description'  => $this->description,
            'createdAt'    => $this->created_at?->toIso8601String(),
        ];
    }
}
