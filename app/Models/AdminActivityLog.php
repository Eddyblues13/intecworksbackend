<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
