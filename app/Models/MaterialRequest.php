<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_job_id', 'artisan_id', 'items', 'status', 'notes',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function artisan()    { return $this->belongsTo(User::class, 'artisan_id'); }
    public function orders()     { return $this->hasMany(MaterialOrder::class); }

    public function toApiArray(): array
    {
        return [
            'id'           => $this->id,
            'serviceJobId' => (string) $this->service_job_id,
            'artisanId'    => (string) $this->artisan_id,
            'items'        => $this->items ?? [],
            'status'       => $this->status,
            'notes'        => $this->notes,
            'createdAt'    => $this->created_at?->toIso8601String(),
        ];
    }
}
