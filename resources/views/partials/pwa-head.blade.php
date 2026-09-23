@php
    $pwaIconVersion = \App\Models\Setting::getIconVersion();
@endphp
<!-- PWA Web App Manifest (Single valid declaration) -->
<link rel="manifest" href="/manifest.webmanifest?v={{ $pwaIconVersion }}">
<meta name="theme-color" content="#047857">

<!-- Mobile & Android Icons -->
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png?v={{ $pwaIconVersion }}">
<link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png?v={{ $pwaIconVersion }}">

<!-- Mobile & iOS PWA Meta Tags -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SiPintu">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v={{ $pwaIconVersion }}">
<link rel="mask-icon" href="/icons/icon-maskable-192x192.png?v={{ $pwaIconVersion }}" color="#047857">

<!-- Windows Tile Meta Tags -->
<meta name="application-name" content="SiPintu">
<meta name="msapplication-TileColor" content="#047857">
<meta name="msapplication-TileImage" content="/icons/icon-144x144.png?v={{ $pwaIconVersion }}">

<!-- Early PWA Listener & Fast Service Worker Registration (Must be executed in HEAD) -->
<script>
    window.deferredPWAInstallPrompt = null;
    window.deferredPwaPrompt = null;

    // 1. Capture beforeinstallprompt immediately
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.deferredPWAInstallPrompt = e;
        window.deferredPwaPrompt = e;
        window.dispatchEvent(new CustomEvent('pwa-prompt-ready', { detail: e }));
    });

    // 2. Track installation completion
    window.addEventListener('appinstalled', function () {
        window.deferredPWAInstallPrompt = null;
        window.deferredPwaPrompt = null;
        window.dispatchEvent(new CustomEvent('pwa-app-installed'));
    });

    // 3. Register Service Worker immediately
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function (err) {
            console.warn('[PWA] SW register note:', err);
        });
    }
</script>
