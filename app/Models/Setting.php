<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::where('key', $key)->first();
            return $setting && !is_null($setting->value) ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set or update a setting by key.
     */
    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get the accessible public URL for the Website Logo.
     */
    public static function getLogoUrl(): string
    {
        $path = static::get('site_logo');

        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
                return $path;
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return asset('images/logo-smkn1bangsri.png');
    }

    /**
     * Get the accessible public URL for the Login Background Image.
     */
    public static function getLoginBgUrl(): ?string
    {
        $path = static::get('login_background');

        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
                return $path;
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return null;
    }
}
