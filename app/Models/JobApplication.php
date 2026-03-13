<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = ['service_job_id', 'artisan_id', 'status', 'cover_note'];

    public function serviceJob() { return $this->belongsTo(ServiceJob::class); }
    public function artisan()    { return $this->belongsTo(User::class, 'artisan_id'); }
}
