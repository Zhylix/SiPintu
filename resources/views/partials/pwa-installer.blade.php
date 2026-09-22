<!-- SiPintu PWA Installer Component -->
<div id="sipintu-pwa-container" class="relative z-50">
    <!-- Floating Mobile Install Banner (Bottom-Sheet / Floating Card) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-sm bg-white/95 backdrop-blur-md border-2 border-emerald-500/30 rounded-2xl shadow-2xl p-4 transition-all duration-300 transform translate-y-full">
        <div class="flex items-start gap-3.5">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="SiPintu Icon" class="w-12 h-12 rounded-xl object-contain ring-2 ring-emerald-500/30 p-1 bg-emerald-50 shrink-0 shadow-sm">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-emerald-950 tracking-tight flex items-center gap-1.5">
                        SiPintu Mobile
                        <span class="px-1.5 py-0.2 text-[9px] font-extrabold bg-emerald-100 text-emerald-800 rounded-full border border-emerald-300">PWA</span>
                    </h3>
                    <button onclick="dismissPwaBanner()" class="text-slate-400 hover:text-slate-600 p-0.5 rounded-lg transition-colors" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-snug">Pasang di Layar Utama HP untuk akses cepat tanpa repot buka browser.</p>
                
                <div class="flex items-center gap-2 mt-3">
                    <button onclick="window.installSiPintuPwa()" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-bold text-xs shadow-md shadow-emerald-700/20 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Pasang Aplikasi
                    </button>
                    <button onclick="dismissPwaBanner()" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors cursor-pointer">
                        Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- iOS Safari Manual Installation Modal Guide -->
    <div id="pwa-ios-modal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 transform transition-all animate-in fade-in zoom-in-95">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('icons/icon-192x192.png') }}" class="w-10 h-10 rounded-xl object-contain ring-2 ring-emerald-500/30 p-0.5 bg-emerald-50">
                    <div>
                        <h4 class="font-extrabold text-sm text-emerald-950">Pasang SiPintu di iPhone/iPad</h4>
                        <p class="text-[11px] text-slate-500">Panduan instalasi via Safari</p>
                    </div>
                </div>
                <button onclick="closeIosModal()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="py-4 space-y-3.5 text-xs text-slate-700">
                <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0">1</span>
                    <div>Ketuk tombol <strong>Bagikan (Share)</strong> <svg class="inline-block w-4 h-4 text-blue-500 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg> di bar menu bawah Safari.</div>
                </div>
                <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0">2</span>
                    <div>Gulir opsi ke bawah lalu pilih menu <strong>"Tambahkan ke Layar Utama" (Add to Home Screen)</strong> ➕.</div>
                </div>
                <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0">3</span>
                    <div>Ketuk <strong>"Tambah"</strong> di pojok kanan atas. Icon SiPintu akan muncul di Layar Utama iPhone/iPad Anda!</div>
                </div>
            </div>

            <button onclick="closeIosModal()" class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md shadow-emerald-700/20">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        // 1. Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (reg) {
                        // Registration successful
                    })
                    .catch(function (err) {
                        console.warn('Service Worker registration failed:', err);
                    });
            });
        }

        // 2. Standalone Detection (Check if already running as installed App)
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true ||
                           document.referrer.includes('android-app://');

        // Check if iOS device
        var isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        // Save deferredPrompt event globally
        window.deferredPwaPrompt = null;

        window.addEventListener('beforeinstallprompt', function (e) {
            // Prevent standard mini-infobar
            e.preventDefault();
            window.deferredPwaPrompt = e;

            // Show manual install buttons if any on page
            updateInstallButtons(true);

            // Show floating banner if not running in standalone and not recently dismissed
            if (!isStandalone) {
                var dismissedAt = localStorage.getItem('sipintu_pwa_dismissed');
                var sevenDays = 7 * 24 * 60 * 60 * 1000;
                if (!dismissedAt || (Date.now() - parseInt(dismissedAt, 10)) > sevenDays) {
                    showPwaBanner();
                }
            }
        });

        window.addEventListener('appinstalled', function () {
            window.deferredPwaPrompt = null;
            hidePwaBanner();
            updateInstallButtons(false);
            if (typeof window.showToastNotification === 'function') {
                window.showToastNotification('success', 'Aplikasi SiPintu berhasil dipasang di Layar Utama HP Anda!');
            }
        });

        // Trigger installation flow
        window.installSiPintuPwa = function () {
            if (window.deferredPwaPrompt) {
                window.deferredPwaPrompt.prompt();
                window.deferredPwaPrompt.userChoice.then(function (choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        hidePwaBanner();
                    }
                    window.deferredPwaPrompt = null;
                });
            } else if (isIos && !isStandalone) {
                showIosModal();
            } else if (isStandalone) {
                alert('Aplikasi SiPintu sudah terpasang dan sedang berjalan dalam mode aplikasi.');
            } else {
                // For desktop Chrome or browsers where prompt cannot be triggered directly:
                alert('Untuk memasang SiPintu, buka menu browser (titik tiga di kanan atas) lalu pilih "Instal Aplikasi" atau "Tambahkan ke Layar Utama".');
            }
        };

        window.dismissPwaBanner = function () {
            localStorage.setItem('sipintu_pwa_dismissed', Date.now().toString());
            hidePwaBanner();
        };

        function showPwaBanner() {
            var banner = document.getElementById('pwa-install-banner');
            if (banner) {
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

        window.showIosModal = function () {
            var modal = document.getElementById('pwa-ios-modal');
            if (modal) modal.classList.remove('hidden');
        };

        window.closeIosModal = function () {
            var modal = document.getElementById('pwa-ios-modal');
            if (modal) modal.classList.add('hidden');
        };

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

        // Initialize button visibility on DOM load
        document.addEventListener('DOMContentLoaded', function () {
            updateInstallButtons(true);
        });
    })();
</script>
