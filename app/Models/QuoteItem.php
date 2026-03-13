<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = ['quote_id', 'type', 'description', 'quantity', 'unit_price', 'total_price'];

    protected $casts = ['unit_price' => 'float', 'total_price' => 'float'];

    public function quote() { return $this->belongsTo(Quote::class); }

    public function toApiArray(): array
    {
        return [
            'id'          => (string) $this->id,
            'quoteId'     => (string) $this->quote_id,
            'type'        => $this->type,
            'description' => $this->description,
            'quantity'    => $this->quantity,
            'unitPrice'   => $this->unit_price,
            'totalPrice'  => $this->total_price,
        ];
    }
}
