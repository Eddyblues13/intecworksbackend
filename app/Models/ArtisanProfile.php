<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtisanProfile extends Model
{
    protected $fillable = [
        'user_id', 'gov_id_url', 'skill_proof_urls', 'verification_status',
        'verification_notes', 'skill_categories', 'service_radius',
        'trust_score', 'skill_badge', 'tier',
        'current_active_jobs', 'current_scheduled_jobs',
        'is_available', 'approved_at',
    ];

    protected $casts = [
        'skill_proof_urls'  => 'array',
        'skill_categories'  => 'array',
        'service_radius'    => 'float',
        'trust_score'       => 'float',
        'current_active_jobs'    => 'integer',
        'current_scheduled_jobs' => 'integer',
        'is_available'      => 'boolean',
        'approved_at'       => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function toApiArray(): array
    {
        return [
            'id'                   => (string) $this->id,
            'userId'               => (string) $this->user_id,
            'govIdUrl'             => $this->gov_id_url,
            'skillProofUrls'       => $this->skill_proof_urls ?? [],
            'verificationStatus'   => $this->verification_status,
            'verificationNotes'    => $this->verification_notes,
            'skillCategories'      => $this->skill_categories ?? [],
            'serviceRadius'        => $this->service_radius,
            'trustScore'           => $this->trust_score,
            'skillBadge'           => $this->skill_badge,
            'tier'                 => $this->tier,
            'currentActiveJobs'    => $this->current_active_jobs,
            'currentScheduledJobs' => $this->current_scheduled_jobs,
            'isAvailable'          => $this->is_available,
            'approvedAt'           => $this->approved_at?->toIso8601String(),
        ];
    }
}
