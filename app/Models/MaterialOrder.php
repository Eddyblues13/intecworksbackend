<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MaterialOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'material_request_id', 'supplier_id', 'total_amount', 'status',
        'quote_items', 'delivery_notes', 'delivery_proof_images',
        'delivered_at', 'confirmed_at',
    ];

    protected $casts = [
        'total_amount'          => 'float',
        'quote_items'           => 'array',
        'delivery_proof_images' => 'array',
        'delivered_at'          => 'datetime',
        'confirmed_at'          => 'datetime',
    ];

    public function materialRequest() { return $this->belongsTo(MaterialRequest::class); }
    public function supplier()        { return $this->belongsTo(User::class, 'supplier_id'); }

    public function toApiArray(): array
    {
        return [
            'id'                  => $this->id,
            'materialRequestId'   => $this->material_request_id,
            'supplierId'          => $this->supplier_id ? (string) $this->supplier_id : null,
            'totalAmount'         => $this->total_amount,
            'status'              => $this->status,
            'quoteItems'          => $this->quote_items ?? [],
            'deliveryNotes'       => $this->delivery_notes,
            'deliveryProofImages' => $this->delivery_proof_images ?? [],
            'deliveredAt'         => $this->delivered_at?->toIso8601String(),
            'confirmedAt'         => $this->confirmed_at?->toIso8601String(),
            'createdAt'           => $this->created_at?->toIso8601String(),
        ];
    }
}
