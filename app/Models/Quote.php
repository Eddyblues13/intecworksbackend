<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'service_job_id', 'artisan_id', 'status',
        'total_amount', 'labor_total', 'material_total',
        'timeline', 'notes',
        'submitted_at', 'admin_reviewed_at', 'client_responded_at', 'expires_at',
    ];

    protected $casts = [
        'total_amount'       => 'float',
        'labor_total'        => 'float',
        'material_total'     => 'float',
        'submitted_at'       => 'datetime',
        'admin_reviewed_at'  => 'datetime',
        'client_responded_at'=> 'datetime',
        'expires_at'         => 'datetime',
    ];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function artisan()    { return $this->belongsTo(User::class, 'artisan_id'); }
    public function items()      { return $this->hasMany(QuoteItem::class); }

    public function toApiArray(): array
    {
        return [
            'id'               => (string) $this->id,
            'jobId'            => (string) $this->service_job_id,
            'artisanId'        => (string) $this->artisan_id,
            'status'           => $this->status,
            'totalAmount'      => $this->total_amount,
            'laborTotal'       => $this->labor_total,
            'materialTotal'    => $this->material_total,
            'timeline'         => $this->timeline,
            'notes'            => $this->notes,
            'items'            => $this->items->map->toApiArray()->toArray(),
            'submittedAt'      => $this->submitted_at?->toIso8601String(),
            'adminReviewedAt'  => $this->admin_reviewed_at?->toIso8601String(),
            'clientRespondedAt'=> $this->client_responded_at?->toIso8601String(),
            'expiresAt'        => $this->expires_at?->toIso8601String(),
        ];
    }
}
