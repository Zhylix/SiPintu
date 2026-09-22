<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PwaIconService
{
    /**
     * Standard icon sizes to generate.
     */
    protected const SIZES = [72, 96, 128, 144, 152, 192, 384, 512];

    /**
     * Regenerate all PWA icons from current active logo.
     */
    public static function generateFromCurrentLogo(): bool
    {
        try {
            $logoPath = Setting::get('site_logo');
            $fullSourcePath = null;

            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                $fullSourcePath = Storage::disk('public')->path($logoPath);
            } else {
                $defaultLogo = public_path('images/logo-smkn1bangsri.png');
                if (file_exists($defaultLogo)) {
                    $fullSourcePath = $defaultLogo;
                }
            }

            if (! $fullSourcePath || ! file_exists($fullSourcePath)) {
                Log::warning('PwaIconService: Source logo not found for PWA icon generation.');
                return false;
            }

            return self::generateIconsFromPath($fullSourcePath);
        } catch (\Throwable $e) {
            Log::error('PwaIconService failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Generate standard, maskable, and apple touch icons from a source file path.
     */
    public static function generateIconsFromPath(string $sourcePath): bool
    {
        if (! extension_loaded('gd')) {
            Log::warning('PwaIconService: PHP GD extension is not loaded.');
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (! $imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'] ?? '';
        $srcImg = match ($mime) {
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (! $srcImg) {
            return false;
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);
        $outDir = public_path('icons');

        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // 1. Generate standard transparent icons
        foreach (self::SIZES as $size) {
            $destImg = imagecreatetruecolor($size, $size);
            imagealphablending($destImg, false);
            imagesavealpha($destImg, true);
            $transColor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
            imagefilledrectangle($destImg, 0, 0, $size, $size, $transColor);
            imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $size, $size, $origW, $origH);
            imagepng($destImg, $outDir . "/icon-{$size}x{$size}.png", 8);
            imagedestroy($destImg);
        }

        // 2. Generate maskable icons (for Android adaptive icons: safe-zone with solid white background)
        foreach ([192, 512] as $size) {
            $maskImg = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($maskImg, 255, 255, 255);
            imagefilledrectangle($maskImg, 0, 0, $size, $size, $white);

            $logoSize = (int) round($size * 0.78);
            $offset = (int) round(($size - $logoSize) / 2);

            imagealphablending($maskImg, true);
            imagecopyresampled($maskImg, $srcImg, $offset, $offset, 0, 0, $logoSize, $logoSize, $origW, $origH);
            imagepng($maskImg, $outDir . "/icon-maskable-{$size}x{$size}.png", 8);
            imagedestroy($maskImg);
        }

        // 3. Generate Apple Touch Icon (180x180)
        $appleImg = imagecreatetruecolor(180, 180);
        $white = imagecolorallocate($appleImg, 255, 255, 255);
        imagefilledrectangle($appleImg, 0, 0, 180, 180, $white);
        $appleLogoSize = (int) round(180 * 0.82);
        $appleOffset = (int) round((180 - $appleLogoSize) / 2);
        imagealphablending($appleImg, true);
        imagecopyresampled($appleImg, $srcImg, $appleOffset, $appleOffset, 0, 0, $appleLogoSize, $appleLogoSize, $origW, $origH);
        imagepng($appleImg, public_path('apple-touch-icon.png'), 8);
        imagedestroy($appleImg);

        imagedestroy($srcImg);

        return true;
    }
}
