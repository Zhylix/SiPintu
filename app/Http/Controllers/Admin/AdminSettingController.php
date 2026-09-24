<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\PwaIconService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingController extends Controller
{
    /**
     * Display logo, icon PWA, & login background settings management page.
     */
    public function index()
    {
        $siteLogo = Setting::get('site_logo');
        $siteLogoUrl = Setting::getLogoUrl();
        $isCustomLogo = ! empty($siteLogo) && Storage::disk('public')->exists($siteLogo);

        $siteIcon = Setting::get('site_icon');
        $siteIconUrl = Setting::getIconUrl();
        $isCustomIcon = ! empty($siteIcon) && Storage::disk('public')->exists($siteIcon);
        $isIconConnectedToLogo = Setting::isIconConnectedToLogo();
        $iconVersion = Setting::getIconVersion();

        $loginBg = Setting::get('login_background');
        $loginBgUrl = Setting::getLoginBgUrl();
        $isCustomLoginBg = ! empty($loginBg) && Storage::disk('public')->exists($loginBg);

        return view('admin.settings.index', compact(
            'siteLogo',
            'siteLogoUrl',
            'isCustomLogo',
            'siteIcon',
            'siteIconUrl',
            'isCustomIcon',
            'isIconConnectedToLogo',
            'iconVersion',
            'loginBg',
            'loginBgUrl',
            'isCustomLoginBg'
        ));
    }

    /**
     * Automatically updates & regenerates connected PWA icons if icon is synced with logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ], [
            'logo.required' => 'Pilih berkas gambar logo terlebih dahulu.',
            'logo.image' => 'Berkas logo harus berupa gambar.',
            'logo.mimes' => 'Format logo harus JPEG, PNG, JPG, WEBP, atau SVG.',
            'logo.max' => 'Ukuran berkas logo maksimal 2 MB.',
        ]);

        $oldLogo = Setting::get('site_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $request->file('logo')->store('settings', 'public');
        Setting::set('site_logo', $path);

        // Auto-regenerate PWA app icons for mobile & home screen install
        PwaIconService::generateFromCurrentLogo();

        AuditLogger::log('update_site_logo', ['path' => $path], auth()->id());

        $message = Setting::isIconConnectedToLogo()
            ? 'Logo website berhasil diperbarui dan tersambung otomatis dengan Icon PWA!'
            : 'Logo website berhasil diperbarui!';

        return back()->with('success', $message);
    }

    /**
     * Reset / Remove Website Logo to Default.
     */
    public function destroyLogo(): RedirectResponse
    {
        $oldLogo = Setting::get('site_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        Setting::set('site_logo', null);

        // Auto-regenerate PWA app icons to default logo
        PwaIconService::generateFromCurrentLogo();

        AuditLogger::log('reset_site_logo', [], auth()->id());

        return back()->with('info', 'Logo website berhasil dikembalikan ke logo bawaan.');
    }

    /**
     * Upload / Update Dedicated PWA & App Icon.
     */
    public function updateIcon(Request $request): RedirectResponse
    {
        $request->validate([
            'icon' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ], [
            'icon.required' => 'Pilih berkas gambar icon terlebih dahulu.',
            'icon.image' => 'Berkas icon harus berupa gambar.',
            'icon.mimes' => 'Format icon harus JPEG, PNG, JPG, WEBP, atau SVG.',
            'icon.max' => 'Ukuran berkas icon maksimal 2 MB.',
        ]);

        $oldIcon = Setting::get('site_icon');
        if ($oldIcon && Storage::disk('public')->exists($oldIcon)) {
            Storage::disk('public')->delete($oldIcon);
        }

        $path = $request->file('icon')->store('settings', 'public');
        Setting::set('site_icon', $path);

        // Auto-regenerate all PWA icon variants from this new icon
        PwaIconService::generateFromCurrentLogo();

        AuditLogger::log('update_site_icon', ['path' => $path], auth()->id());

        return back()->with('success', 'Icon PWA & Favicon berhasil diperbarui!');
    }

    /**
     * Connect PWA Icon to Website Logo (Remove custom icon, sync with logo).
     */
    public function destroyIcon(): RedirectResponse
    {
        $oldIcon = Setting::get('site_icon');
        if ($oldIcon && Storage::disk('public')->exists($oldIcon)) {
            Storage::disk('public')->delete($oldIcon);
        }

        Setting::set('site_icon', null);

        // Auto-regenerate PWA app icons from active website logo (fully connected & identical)
        PwaIconService::generateFromCurrentLogo();

        AuditLogger::log('connect_icon_to_logo', [], auth()->id());

        return back()->with('success', 'Icon PWA berhasil disambungkan dengan Logo Website! Ikon kini identik dan mengikuti logo website secara otomatis.');
    }

    /**
     * Upload / Update Login Page Background.
     */
    public function updateLoginBg(Request $request): RedirectResponse
    {
        $request->validate([
            'login_bg' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
        ], [
            'login_bg.required' => 'Pilih berkas gambar background login terlebih dahulu.',
            'login_bg.image' => 'Berkas background harus berupa gambar.',
            'login_bg.mimes' => 'Format gambar background harus JPEG, PNG, JPG, WEBP, atau SVG.',
            'login_bg.max' => 'Ukuran berkas background maksimal 5 MB.',
        ]);

        $oldBg = Setting::get('login_background');
        if ($oldBg && Storage::disk('public')->exists($oldBg)) {
            Storage::disk('public')->delete($oldBg);
        }

        $path = $request->file('login_bg')->store('settings', 'public');
        Setting::set('login_background', $path);

        AuditLogger::log('update_login_background', ['path' => $path], auth()->id());

        return back()->with('success', 'Background halaman login berhasil diperbarui!');
    }

    /**
     * Reset / Remove Login Background to Default.
     */
    public function destroyLoginBg(): RedirectResponse
    {
        $oldBg = Setting::get('login_background');
        if ($oldBg && Storage::disk('public')->exists($oldBg)) {
            Storage::disk('public')->delete($oldBg);
        }

        Setting::set('login_background', null);

        AuditLogger::log('reset_login_background', [], auth()->id());

        return back()->with('info', 'Background halaman login berhasil dikembalikan ke tampilan awal.');
    }
}
