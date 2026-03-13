<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['category_id', 'name', 'base_price'];

    protected $casts = ['base_price' => 'float'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function toApiArray(): array
    {
        return [
            'id'         => (string) $this->id,
            'categoryId' => (string) $this->category_id,
            'name'       => $this->name,
            'basePrice'  => $this->base_price,
        ];
    }
}
