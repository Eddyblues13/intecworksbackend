<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'pending_balance', 'total_earned', 'total_withdrawn',
    ];

    protected $casts = [
        'balance'          => 'float',
        'pending_balance'  => 'float',
        'total_earned'     => 'float',
        'total_withdrawn'  => 'float',
    ];

    public function user()         { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(WalletTransaction::class); }

    public function toApiArray(): array
    {
        return [
            'id'              => (string) $this->id,
            'userId'          => (string) $this->user_id,
            'balance'         => $this->balance,
            'pendingBalance'  => $this->pending_balance,
            'totalEarned'     => $this->total_earned,
            'totalWithdrawn'  => $this->total_withdrawn,
        ];
    }
}
