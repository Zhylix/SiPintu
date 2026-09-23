<!-- SiPintu PWA Direct Installer Component -->
<div id="sipintu-pwa-container" class="relative z-50">
    <!-- 1. Floating Mobile Install Banner (Bottom Bar) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md bg-white/98 backdrop-blur-md border-2 border-emerald-600/30 rounded-2xl shadow-2xl p-4 transition-all duration-300 transform translate-y-full z-50">
        <div class="flex items-center gap-3.5">
            <img src="/icons/icon-192x192.png" alt="SiPintu Icon" class="w-12 h-12 rounded-xl object-contain ring-2 ring-emerald-500/30 p-1 bg-emerald-50 shrink-0 shadow-sm">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-emerald-950 tracking-tight">SiPintu Mobile</h3>
                    <button onclick="dismissPwaBanner()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors cursor-pointer" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 leading-snug">Pasang aplikasi SiPintu di Layar Utama HP untuk akses cepat & lancar.</p>
                
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

        function getPrompt() {
            return window.deferredPWAInstallPrompt || window.deferredPwaPrompt || null;
        }

        function showInstallUi() {
            if (isStandalone) {
                updateAllInstallButtons(false);
                return;
            }

            var dismissedAt = localStorage.getItem('sipintu_pwa_dismissed');
            var threeDays = 3 * 24 * 60 * 60 * 1000;

            if (!dismissedAt || (Date.now() - parseInt(dismissedAt, 10)) > threeDays) {
                showPwaBanner();
            } else if (isMobile) {
                showPwaFab();
            }
            updateAllInstallButtons(true);
        }

        // Cek jika prompt sudah tertangkap sebelumnya di HEAD
        if (getPrompt()) {
            showInstallUi();
        }

        window.addEventListener('pwa-prompt-ready', function () {
            showInstallUi();
        });

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.deferredPWAInstallPrompt = e;
            window.deferredPwaPrompt = e;
            showInstallUi();
        });

        function pwaNotify(type, message, title) {
            if (window.toast && typeof window.toast[type] === 'function') {
                window.toast[type](message, title);
            } else if (typeof window.showToast === 'function') {
                window.showToast(message, type, title);
            } else {
                alert(message);
            }
        }

        // Event saat aplikasi selesai dipasang
        window.addEventListener('appinstalled', function () {
            window.deferredPWAInstallPrompt = null;
            window.deferredPwaPrompt = null;
            hidePwaBanner();
            hidePwaFab();
            updateAllInstallButtons(false);
            pwaNotify('success', 'Aplikasi SiPintu berhasil dipasang di Layar Utama HP Anda!', 'SiPintu Terpasang');
        });

        // Eksekusi Pemicu Native Langsung (1-Klik)
        function triggerNativePrompt(promptEvent, onComplete) {
            try {
                promptEvent.prompt();
                promptEvent.userChoice.then(function (choiceResult) {
                    if (choiceResult && choiceResult.outcome === 'accepted') {
                        hidePwaBanner();
                        hidePwaFab();
                        pwaNotify('success', 'Sedang memasang SiPintu ke Layar Utama...', 'Memasang');
                    }
                    window.deferredPWAInstallPrompt = null;
                    window.deferredPwaPrompt = null;
                    if (typeof onComplete === 'function') onComplete();
                }).catch(function (err) {
                    console.warn('Install prompt choice error:', err);
                    if (typeof onComplete === 'function') onComplete();
                });
            } catch (err) {
                console.warn('triggerNativePrompt error:', err);
                if (typeof onComplete === 'function') onComplete();
            }
        }

        // Pemicu Klik Pasang
        window.installSiPintuPwa = function (btnElement) {
            if (isStandalone) {
                pwaNotify('info', 'Aplikasi SiPintu sudah terpasang dan aktif di perangkat Anda.', 'SiPintu');
                return;
            }

            var prompt = getPrompt();

            // 1. Jika event native Android sudah siap, LANGSUNG BUKA DIALOG SEKARANG
            if (prompt) {
                triggerNativePrompt(prompt);
                return;
            }

            // 2. Jika event belum siap di detik ini (misal baru buka halaman),
            // tunggu hingga event tertangkap (masih dalam batas user gesture)
            var targetBtn = btnElement || document.querySelector('[data-pwa-install-btn]');
            var originalText = targetBtn ? targetBtn.innerHTML : '';
            if (targetBtn) {
                targetBtn.innerHTML = '<span>Menyiapkan...</span>';
            }

            var checkCount = 0;
            var pollInterval = setInterval(function () {
                checkCount++;
                var p = getPrompt();
                if (p) {
                    clearInterval(pollInterval);
                    if (targetBtn && originalText) targetBtn.innerHTML = originalText;
                    triggerNativePrompt(p);
                    return;
                }

                if (checkCount >= 25) { // 2.5 detik
                    clearInterval(pollInterval);
                    if (targetBtn && originalText) targetBtn.innerHTML = originalText;

                    var isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
                    if (isIos) {
                        pwaNotify('info', 'Di iPhone/iPad: Tekan ikon Bagikan (Share) di Safari, lalu pilih "Tambah ke Layar Utama".', 'Instal di iPhone');
                    } else {
                        pwaNotify('info', 'Aplikasi kemungkinan sudah terpasang di HP Anda, atau Anda dapat memasangnya melalui menu titik tiga (⋮) di kanan atas browser -> "Instal aplikasi".', 'Instal Aplikasi');
                    }
                }
            }, 100);
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

        function updateAllInstallButtons(available) {
            var buttons = document.querySelectorAll('[data-pwa-install-btn]');
            buttons.forEach(function (btn) {
                if (isStandalone) {
                    btn.classList.add('hidden');
                } else {
                    btn.classList.remove('hidden');
                }
            });

            var profileBadge = document.getElementById('profile-pwa-badge');
            if (profileBadge) {
                if (isStandalone) {
                    profileBadge.innerText = '✓ Terpasang';
                    profileBadge.className = 'px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300';
                } else {
                    profileBadge.innerText = 'Tersedia';
                    profileBadge.className = 'px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateAllInstallButtons(true);
        });
    })();
</script>
