<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $fillable = [
        'service_job_id',
        'reported_by_id',
        'against_id',
        'reason',
        'status',
        'resolution',
        'admin_notes',
        'resolved_by_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function against()
    {
        return $this->belongsTo(User::class, 'against_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
