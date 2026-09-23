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

            return $setting && ! is_null($setting->value) ? $setting->value : $default;
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
                return '/storage/' . ltrim($path, '/');
            }
        }

        return '/images/logo-smkn1bangsri.png';
    }

    /**
     * Get the accessible public URL for the Login Background Image.
     * Defaults to logo-smkn1bangsri.png if no custom background image is uploaded.
     */
    public static function getLoginBgUrl(): string
    {
        $path = static::get('login_background');

        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
                return $path;
            }

            if (Storage::disk('public')->exists($path)) {
                return '/storage/' . ltrim($path, '/');
            }
        }

        return '/images/logo-smkn1bangsri.png';
    }

    /**
     * Get the accessible public URL for the Website / PWA Icon (Favicon & App Icon).
     * If a separate custom icon is set ('site_icon'), use it.
     * Otherwise, directly connect & fallback to the Website Logo ('site_logo').
     */
    public static function getIconUrl(): string
    {
        $path = static::get('site_icon');

        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
                return $path;
            }

            if (Storage::disk('public')->exists($path)) {
                return '/storage/' . ltrim($path, '/');
            }
        }

        return static::getLogoUrl();
    }

    /**
     * Check if the PWA Icon is connected directly with the Website Logo.
     */
    public static function isIconConnectedToLogo(): bool
    {
        $customIcon = static::get('site_icon');
        return empty($customIcon) || ! Storage::disk('public')->exists($customIcon);
    }

    /**
     * Get the current PWA Icon version timestamp for cache-busting.
     */
    public static function getIconVersion(): string
    {
        return (string) (static::get('pwa_icon_version') ?? '1');
    }
}
