<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PwaIconService
{
    /**
     * Standard icon sizes to generate, ordered sequentially from smallest to largest.
     */
    public const SIZES = [72, 96, 128, 144, 152, 192, 384, 512];

    /**
     * Regenerate all PWA icons from current active logo / icon.
     * When icon is connected to the logo (no distinct site_icon), it uses the site_logo.
     */
    public static function generateFromCurrentLogo(): bool
    {
        try {
            $iconPath = Setting::get('site_icon');
            $fullSourcePath = null;

            if ($iconPath && Storage::disk('public')->exists($iconPath)) {
                $fullSourcePath = Storage::disk('public')->path($iconPath);
            } else {
                $logoPath = Setting::get('site_logo');
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    $fullSourcePath = Storage::disk('public')->path($logoPath);
                } else {
                    $defaultLogo = public_path('images/logo-smkn1bangsri.png');
                    if (file_exists($defaultLogo)) {
                        $fullSourcePath = $defaultLogo;
                    }
                }
            }

            if (! $fullSourcePath || ! file_exists($fullSourcePath)) {
                Log::warning('PwaIconService: Source logo/icon not found for PWA icon generation.');
                return false;
            }

            return self::generateIconsFromPath($fullSourcePath);
        } catch (\Throwable $e) {
            Log::error('PwaIconService failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Generate standard, maskable, apple touch, and favicon icons from a source file path.
     * Preserves aspect ratio with automatic transparent centering (no stretching).
     */
    public static function generateIconsFromPath(string $sourcePath): bool
    {
        if (! extension_loaded('gd')) {
            Log::warning('PwaIconService: PHP GD extension is not loaded.');
            return false;
        }

        $tempPngPath = null;
        $imagePath = $sourcePath;
        $isSvg = str_ends_with(strtolower($sourcePath), '.svg') || (@mime_content_type($sourcePath) === 'image/svg+xml');

        // Convert SVG to high-res PNG using ImageMagick CLI if available
        if ($isSvg) {
            $convertBin = trim((string) @shell_exec('which convert 2>/dev/null'));
            if ($convertBin && file_exists($convertBin)) {
                $tempPngPath = tempnam(sys_get_temp_dir(), 'pwa_svg_') . '.png';
                $cmd = sprintf(
                    '%s -background none -density 300 %s -resize 1024x1024 %s',
                    escapeshellcmd($convertBin),
                    escapeshellarg($sourcePath),
                    escapeshellarg($tempPngPath)
                );
                @exec($cmd);
                if (file_exists($tempPngPath) && filesize($tempPngPath) > 0) {
                    $imagePath = $tempPngPath;
                }
            }
        }

        $imageInfo = @getimagesize($imagePath);
        if (! $imageInfo) {
            if ($tempPngPath && file_exists($tempPngPath)) {
                @unlink($tempPngPath);
            }
            return false;
        }

        $mime = $imageInfo['mime'] ?? '';
        $srcImg = match ($mime) {
            'image/png' => @imagecreatefrompng($imagePath),
            'image/jpeg' => @imagecreatefromjpeg($imagePath),
            'image/webp' => @imagecreatefromwebp($imagePath),
            default => null,
        };

        if (! $srcImg) {
            if ($tempPngPath && file_exists($tempPngPath)) {
                @unlink($tempPngPath);
            }
            return false;
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);
        $outDir = public_path('icons');

        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // 1. Generate standard transparent icons (with aspect ratio preservation & centering)
        foreach (self::SIZES as $size) {
            $destImg = imagecreatetruecolor($size, $size);
            imagealphablending($destImg, false);
            imagesavealpha($destImg, true);
            $transColor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
            imagefilledrectangle($destImg, 0, 0, $size, $size, $transColor);

            $ratio = min($size / $origW, $size / $origH);
            $targetW = max(1, (int) round($origW * $ratio));
            $targetH = max(1, (int) round($origH * $ratio));
            $dstX = (int) round(($size - $targetW) / 2);
            $dstY = (int) round(($size - $targetH) / 2);

            imagealphablending($destImg, true);
            imagecopyresampled($destImg, $srcImg, $dstX, $dstY, 0, 0, $targetW, $targetH, $origW, $origH);
            imagealphablending($destImg, false);
            imagesavealpha($destImg, true);
            imagepng($destImg, $outDir . "/icon-{$size}x{$size}.png", 8);
            imagedestroy($destImg);
        }

        // 2. Generate maskable icons for Android adaptive launcher (safe-zone 76% on clean white squircle)
        foreach ([192, 512] as $size) {
            $maskImg = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($maskImg, 255, 255, 255);
            imagefilledrectangle($maskImg, 0, 0, $size, $size, $white);

            $maxInner = (int) round($size * 0.76);
            $ratio = min($maxInner / $origW, $maxInner / $origH);
            $targetW = max(1, (int) round($origW * $ratio));
            $targetH = max(1, (int) round($origH * $ratio));
            $dstX = (int) round(($size - $targetW) / 2);
            $dstY = (int) round(($size - $targetH) / 2);

            imagealphablending($maskImg, true);
            imagecopyresampled($maskImg, $srcImg, $dstX, $dstY, 0, 0, $targetW, $targetH, $origW, $origH);
            imagepng($maskImg, $outDir . "/icon-maskable-{$size}x{$size}.png", 8);
            imagedestroy($maskImg);
        }

        // 3. Generate Apple Touch Icon (180x180)
        $appleImg = imagecreatetruecolor(180, 180);
        $white = imagecolorallocate($appleImg, 255, 255, 255);
        imagefilledrectangle($appleImg, 0, 0, 180, 180, $white);

        $maxInner = (int) round(180 * 0.82);
        $ratio = min($maxInner / $origW, $maxInner / $origH);
        $targetW = max(1, (int) round($origW * $ratio));
        $targetH = max(1, (int) round($origH * $ratio));
        $dstX = (int) round((180 - $targetW) / 2);
        $dstY = (int) round((180 - $targetH) / 2);

        imagealphablending($appleImg, true);
        imagecopyresampled($appleImg, $srcImg, $dstX, $dstY, 0, 0, $targetW, $targetH, $origW, $origH);
        imagepng($appleImg, public_path('apple-touch-icon.png'), 8);
        imagedestroy($appleImg);

        imagedestroy($srcImg);

        if ($tempPngPath && file_exists($tempPngPath)) {
            @unlink($tempPngPath);
        }

        // Bump the version timestamp so browsers & PWA engine invalidate cached icons immediately
        $version = (string) time();
        Setting::set('pwa_icon_version', $version);

        // Update the static manifest.webmanifest and manifest.json files as on-disk fallback
        self::writeManifestFiles($version);

        return true;
    }

    /**
     * Get manifest data array with ordered icons and version query parameter.
     */
    public static function getManifestData(?string $version = null): array
    {
        $v = $version ?? Setting::getIconVersion();

        $icons = [];
        // Sequential transparent icons ordered from smallest to largest
        foreach (self::SIZES as $size) {
            $icons[] = [
                'src' => "/icons/icon-{$size}x{$size}.png?v={$v}",
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any',
            ];
        }

        // Maskable icons for Android adaptive launcher
        foreach ([192, 512] as $size) {
            $icons[] = [
                'src' => "/icons/icon-maskable-{$size}x{$size}.png?v={$v}",
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'maskable',
            ];
        }

        return [
            'name' => 'SiPintu',
            'short_name' => 'SiPintu',
            'description' => 'Sistem Informasi Pintu Masuk SMKN 1 Bangsri',
            'start_url' => '/',
            'scope' => '/',
            'id' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#047857',
            'icons' => $icons,
        ];
    }

    /**
     * Write manifest array to public/manifest.webmanifest and public/manifest.json.
     */
    public static function writeManifestFiles(?string $version = null): void
    {
        try {
            $data = self::getManifestData($version);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            file_put_contents(public_path('manifest.webmanifest'), $json);
            file_put_contents(public_path('manifest.json'), $json);
        } catch (\Throwable $e) {
            Log::warning('PwaIconService: Failed to write manifest files: ' . $e->getMessage());
        }
    }
}
