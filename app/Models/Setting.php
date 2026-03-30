<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'label', 'type'];

    protected $casts = ['value' => 'string'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->keyBy('key')
            ->map(fn($s) => $s->value)
            ->toArray();
    }
}
