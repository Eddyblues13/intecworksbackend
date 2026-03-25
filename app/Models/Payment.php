<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['service_job_id', 'payer_id', 'amount', 'status', 'method', 'reference', 'purpose'];

    protected $casts = ['amount' => 'float'];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function payer()      { return $this->belongsTo(User::class, 'payer_id'); }

    public function toApiArray(): array
    {
        return [
            'id'        => (string) $this->id,
            'jobId'     => (string) $this->service_job_id,
            'amount'    => $this->amount,
            'status'    => $this->status,
            'method'    => $this->method,
            'reference' => $this->reference,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
