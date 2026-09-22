<!-- SiPintu PWA Direct Installer Component -->
<div id="sipintu-pwa-container" class="relative z-50">
    <!-- 1. Floating Mobile Install Banner (Bottom Bar) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md bg-white/98 backdrop-blur-md border-2 border-emerald-600/30 rounded-2xl shadow-2xl p-4 transition-all duration-300 transform translate-y-full z-50">
        <div class="flex items-center gap-3.5">
            <img src="/icons/icon-192x192.png" alt="SiPintu Icon" class="w-12 h-12 rounded-xl object-contain ring-2 ring-emerald-500/30 p-1 bg-emerald-50 shrink-0 shadow-sm">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-emerald-950 tracking-tight">SiPintu</h3>
                    <button onclick="dismissPwaBanner()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 leading-snug">Pasang aplikasi SiPintu di Layar Utama HP Anda.</p>
                
                <div class="flex items-center gap-2 mt-2.5">
                    <button id="pwa-main-install-btn" onclick="window.installSiPintuPwa(this)" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-bold text-xs shadow-md shadow-emerald-700/20 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span id="pwa-install-btn-text">Pasang Sekarang</span>
                    </button>
                    <button onclick="dismissPwaBanner()" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors cursor-pointer">
                        Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Floating Action Button (FAB) -->
    <div id="pwa-floating-fab" class="hidden fixed bottom-5 right-4 z-40">
        <button onclick="window.installSiPintuPwa(this)" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-emerald-800 text-white border border-emerald-500/40 shadow-xl text-xs font-bold hover:bg-emerald-900 active:scale-95 transition-all cursor-pointer">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span>Pasang App</span>
        </button>
    </div>
</div>

<script>
    (function () {
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true ||
                           document.referrer.includes('android-app://');

        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        var isSecure = window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

        window.deferredPwaPrompt = null;
        var waitingClickBtn = null;

        // 1. Immediate Service Worker Registration
        if ('serviceWorker' in navigator && isSecure) {
            var registerSW = function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function () {})
                    .catch(function (err) {
                        console.warn('SW registration failed:', err);
                    });
            };

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                registerSW();
            } else {
                window.addEventListener('DOMContentLoaded', registerSW);
            }
        }

        // 2. Capture Chrome Native beforeinstallprompt
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.deferredPwaPrompt = e;

            // Jika pengguna sudah sempat mengklik tombol pasang, langsung picu
            if (waitingClickBtn) {
                triggerPrompt(waitingClickBtn);
                waitingClickBtn = null;
            }

            // Tampilkan banner instalasi karena Chrome sudah 100% siap memasang
            if (!isStandalone) {
                var dismissedAt = localStorage.getItem('sipintu_pwa_dismissed');
                var threeDays = 3 * 24 * 60 * 60 * 1000;
                if (!dismissedAt || (Date.now() - parseInt(dismissedAt, 10)) > threeDays) {
                    showPwaBanner();
                } else if (isMobile) {
                    showPwaFab();
                }
            }

            updateInstallButtons(true);
        });

        // 3. App Installed Event
        window.addEventListener('appinstalled', function () {
            window.deferredPwaPrompt = null;
            hidePwaBanner();
            hidePwaFab();
            updateInstallButtons(false);
            if (typeof window.showToastNotification === 'function') {
                window.showToastNotification('success', 'Aplikasi SiPintu berhasil dipasang di Layar Utama.');
            }
        });

        // 4. Trigger Native System Prompt
        function triggerPrompt(triggerBtn) {
            if (!window.deferredPwaPrompt) return;

            window.deferredPwaPrompt.prompt();
            window.deferredPwaPrompt.userChoice.then(function (choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    hidePwaBanner();
                    hidePwaFab();
                }
                window.deferredPwaPrompt = null;
            }).catch(function (err) {
                console.warn('Install prompt error:', err);
            });
        }

        // 5. Universal Direct Install Click
        window.installSiPintuPwa = function (btnElement) {
            if (isStandalone) {
                if (typeof window.showToastNotification === 'function') {
                    window.showToastNotification('info', 'Aplikasi SiPintu sudah terpasang dan aktif di perangkat Anda.');
                }
                return;
            }

            // Cek apakah website dibuka lewat HTTPS
            if (!isSecure) {
                if (typeof window.showToastNotification === 'function') {
                    window.showToastNotification('warning', 'Instalasi aplikasi otomatis di Android mewajibkan akses melalui HTTPS (SSL).');
                } else {
                    alert('Instalasi aplikasi Android mewajibkan akses melalui protokol HTTPS (SSL aktif).');
                }
                return;
            }

            // Jika prompt sistem Android sudah siap, langsung picu seketika (1-klik)
            if (window.deferredPwaPrompt) {
                triggerPrompt(btnElement);
                return;
            }

            // Jika Chrome sedang menginisiasi di latar belakang, tunggu respons
            var textSpan = document.getElementById('pwa-install-btn-text');
            var originalText = textSpan ? textSpan.innerText : '';
            if (textSpan) textSpan.innerText = 'Menyiapkan...';

            waitingClickBtn = btnElement;

            // Batas waktu tunggu pemicu
            setTimeout(function () {
                if (textSpan && originalText) {
                    textSpan.innerText = originalText;
                }
                waitingClickBtn = null;
            }, 3000);
        };

        window.dismissPwaBanner = function () {
            localStorage.setItem('sipintu_pwa_dismissed', Date.now().toString());
            hidePwaBanner();
            if (isMobile && !isStandalone) {
                showPwaFab();
            }
        };

        function showPwaBanner() {
            var banner = document.getElementById('pwa-install-banner');
            if (banner && !isStandalone) {
                banner.classList.remove('hidden');
                setTimeout(function () {
                    banner.classList.remove('translate-y-full');
                }, 50);
            }
        }

        function hidePwaBanner() {
            var banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.classList.add('translate-y-full');
                setTimeout(function () {
                    banner.classList.add('hidden');
                }, 300);
            }
        }

        function showPwaFab() {
            var fab = document.getElementById('pwa-floating-fab');
            if (fab && !isStandalone) {
                fab.classList.remove('hidden');
            }
        }

        function hidePwaFab() {
            var fab = document.getElementById('pwa-floating-fab');
            if (fab) {
                fab.classList.add('hidden');
            }
        }

        function updateInstallButtons(available) {
            var buttons = document.querySelectorAll('[data-pwa-install-btn]');
            buttons.forEach(function (btn) {
                if (isStandalone) {
                    btn.classList.add('hidden');
                } else {
                    btn.classList.remove('hidden');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateInstallButtons(true);
        });
    })();
</script>
