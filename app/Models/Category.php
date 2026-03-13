<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'icon_url', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function toApiArray(): array
    {
        return [
            'id'            => (string) $this->id,
            'name'          => $this->name,
            'iconUrl'       => $this->icon_url,
            'subcategories' => $this->subcategories->map->toApiArray()->toArray(),
        ];
    }
}
