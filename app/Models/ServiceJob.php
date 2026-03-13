<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceJob extends Model
{
    protected $fillable = [
        'client_id', 'artisan_id', 'category_id', 'subcategory_id',
        'job_type', 'description', 'images', 'location', 'lat', 'lng',
        'status', 'scope_classification',
        'before_photos', 'after_photos',
        'completion_notes', 'progress_percent', 'progress_notes',
        'accepted_at', 'started_at', 'completed_at', 'closed_at',
        'inspection_submitted_at', 'scope_classified_at', 'quote_submitted_at',
    ];

    protected $casts = [
        'images'         => 'array',
        'before_photos'  => 'array',
        'after_photos'   => 'array',
        'lat'            => 'float',
        'lng'            => 'float',
        'progress_percent' => 'integer',
        'accepted_at'    => 'datetime',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'closed_at'      => 'datetime',
        'inspection_submitted_at' => 'datetime',
        'scope_classified_at'     => 'datetime',
        'quote_submitted_at'      => 'datetime',
    ];

    public function client()       { return $this->belongsTo(User::class, 'client_id'); }
    public function artisan()      { return $this->belongsTo(User::class, 'artisan_id'); }
    public function category()     { return $this->belongsTo(Category::class); }
    public function subcategory()  { return $this->belongsTo(Subcategory::class); }
    public function applications() { return $this->hasMany(JobApplication::class, 'service_job_id'); }
    public function quotes()       { return $this->hasMany(Quote::class, 'service_job_id'); }
    public function reviews()      { return $this->hasMany(Review::class, 'service_job_id'); }
    public function payments()     { return $this->hasMany(Payment::class, 'service_job_id'); }
    public function inspectionReports() { return $this->hasMany(InspectionReport::class); }
    public function materialRequests()  { return $this->hasMany(MaterialRequest::class); }

    public function toApiArray(): array
    {
        return [
            'id'                  => (string) $this->id,
            'clientId'            => (string) $this->client_id,
            'artisanId'           => $this->artisan_id ? (string) $this->artisan_id : null,
            'categoryId'          => (string) $this->category_id,
            'subcategoryId'       => $this->subcategory_id ? (string) $this->subcategory_id : null,
            'jobType'             => $this->job_type,
            'description'         => $this->description,
            'images'              => $this->images ?? [],
            'beforePhotos'        => $this->before_photos ?? [],
            'afterPhotos'         => $this->after_photos ?? [],
            'location'            => $this->location,
            'lat'                 => $this->lat,
            'lng'                 => $this->lng,
            'status'              => $this->status,
            'scopeClassification' => $this->scope_classification,
            'completionNotes'     => $this->completion_notes,
            'progressPercent'     => $this->progress_percent,
            'progressNotes'       => $this->progress_notes,
            'createdAt'           => $this->created_at?->toIso8601String(),
            'acceptedAt'          => $this->accepted_at?->toIso8601String(),
            'startedAt'           => $this->started_at?->toIso8601String(),
            'completedAt'         => $this->completed_at?->toIso8601String(),
            'closedAt'            => $this->closed_at?->toIso8601String(),
            'inspectionSubmittedAt' => $this->inspection_submitted_at?->toIso8601String(),
            'scopeClassifiedAt'   => $this->scope_classified_at?->toIso8601String(),
            'quoteSubmittedAt'    => $this->quote_submitted_at?->toIso8601String(),
            // Include category name for display
            'categoryName'        => $this->category?->name,
            'subcategoryName'     => $this->subcategory?->name,
            'clientName'          => $this->client?->full_name,
        ];
    }
}
