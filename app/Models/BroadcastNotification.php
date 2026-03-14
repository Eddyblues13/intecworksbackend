<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastNotification extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'target_role',
        'target_user_ids',
    ];

    protected function casts(): array
    {
        return [
            'target_user_ids' => 'array',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
