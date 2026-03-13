<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['service_job_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment'];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function reviewer()   { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function reviewee()   { return $this->belongsTo(User::class, 'reviewee_id'); }

    public function toApiArray(): array
    {
        return [
            'id'         => (string) $this->id,
            'jobId'      => (string) $this->service_job_id,
            'reviewerId' => (string) $this->reviewer_id,
            'revieweeId' => (string) $this->reviewee_id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'createdAt'  => $this->created_at?->toIso8601String(),
        ];
    }
}
