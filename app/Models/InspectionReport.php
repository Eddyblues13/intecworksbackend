<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InspectionReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_job_id', 'artisan_id', 'findings', 'images',
        'condition_rating', 'recommended_scope', 'requires_materials',
        'notes', 'submitted_at',
    ];

    protected $casts = [
        'images'             => 'array',
        'requires_materials' => 'boolean',
        'submitted_at'       => 'datetime',
    ];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function artisan()    { return $this->belongsTo(User::class, 'artisan_id'); }

    public function toApiArray(): array
    {
        return [
            'id'                => $this->id,
            'serviceJobId'      => (string) $this->service_job_id,
            'artisanId'         => (string) $this->artisan_id,
            'findings'          => $this->findings,
            'images'            => $this->images ?? [],
            'conditionRating'   => $this->condition_rating,
            'recommendedScope'  => $this->recommended_scope,
            'requiresMaterials' => $this->requires_materials,
            'notes'             => $this->notes,
            'submittedAt'       => $this->submitted_at?->toIso8601String(),
            'createdAt'         => $this->created_at?->toIso8601String(),
        ];
    }
}
