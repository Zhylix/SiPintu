<!-- PWA Web App Manifest -->
<link rel="manifest" href="/manifest.webmanifest">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#047857">

<!-- Mobile & Android Icons -->
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">

<!-- Mobile & iOS PWA Meta Tags -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SiPintu">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="mask-icon" href="/icons/icon-maskable-192x192.png" color="#047857">

<!-- Windows Tile Meta Tags -->
<meta name="application-name" content="SiPintu">
<meta name="msapplication-TileColor" content="#047857">
<meta name="msapplication-TileImage" content="/icons/icon-144x144.png">

<!-- Early PWA Listener & Service Worker Registration (Must be executed in HEAD) -->
<script>
    // Global PWA Prompt Holders (Compatible with modern Chromium & Reference App)
    window.deferredPWAInstallPrompt = null;
    window.deferredPwaPrompt = null;
    window.pwaPromptSubscribers = window.pwaPromptSubscribers || new Set();

    // 1. Capture beforeinstallprompt immediately
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.deferredPWAInstallPrompt = e;
        window.deferredPwaPrompt = e;

        if (window.pwaPromptSubscribers) {
            window.pwaPromptSubscribers.forEach(function (cb) {
                try { cb(e); } catch (err) {}
            });
        }
        window.dispatchEvent(new CustomEvent('pwa-prompt-ready', { detail: e }));
    });

    // 2. Track when app is installed
    window.addEventListener('appinstalled', function () {
        window.deferredPWAInstallPrompt = null;
        window.deferredPwaPrompt = null;
        if (window.pwaPromptSubscribers) {
            window.pwaPromptSubscribers.forEach(function (cb) {
                try { cb(null); } catch (err) {}
            });
        }
        window.dispatchEvent(new CustomEvent('pwa-app-installed'));
    });

    // 3. Register Service Worker on window load
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function (err) {
                console.warn('[PWA] Service Worker registration note:', err);
            });
        });
    }
</script>
