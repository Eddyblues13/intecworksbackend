<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'description', 'price', 'unit',
        'category', 'images', 'in_stock', 'stock_quantity',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'images'         => 'array',
        'in_stock'       => 'boolean',
        'stock_quantity' => 'integer',
    ];

    public function supplier() { return $this->belongsTo(User::class, 'supplier_id'); }

    public function toApiArray(): array
    {
        return [
            'id'            => (string) $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => (float) $this->price,
            'unit'          => $this->unit,
            'category'      => $this->category,
            'images'        => $this->images ?? [],
            'inStock'       => $this->in_stock,
            'stockQuantity' => $this->stock_quantity,
            'createdAt'     => $this->created_at?->toIso8601String(),
        ];
    }
}
