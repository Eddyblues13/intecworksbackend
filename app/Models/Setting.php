<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting by key, cached gracefully.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = \Illuminate\Support\Facades\Cache::rememberForever("setting.{$key}", function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /**
     * Clear the cache when a setting is updated or saved.
     */
    protected static function booted()
    {
        static::saved(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget("setting.{$setting->key}");
        });
    }
}
