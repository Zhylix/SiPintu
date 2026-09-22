<!-- SiPintu PWA Universal Installer Component -->
<div id="sipintu-pwa-container" class="relative z-50">
    <!-- 1. Floating Mobile Install Banner (Bottom Sheet / Floating Card) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md bg-white/95 backdrop-blur-md border-2 border-emerald-500/40 rounded-3xl shadow-2xl p-4 sm:p-5 transition-all duration-300 transform translate-y-full z-50">
        <div class="flex items-start gap-3.5">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="SiPintu Icon" class="w-13 h-13 rounded-2xl object-contain ring-2 ring-emerald-500/30 p-1 bg-emerald-50 shrink-0 shadow-md">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h3 class="text-sm font-black text-emerald-950 tracking-tight">SiPintu App</h3>
                        <span class="px-2 py-0.5 text-[9px] font-extrabold bg-emerald-100 text-emerald-800 rounded-full border border-emerald-300">Resmi & Ringan</span>
                    </div>
                    <button onclick="dismissPwaBanner()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-snug">Pasang di Layar Utama HP untuk akses cepat tanpa repot buka browser.</p>
                
                <div class="flex items-center gap-2 mt-2 text-[10px] text-emerald-700 font-semibold">
                    <span>Hanya 1 MB</span>
                    <span>&bull;</span>
                    <span>Tanpa Play Store</span>
                    <span>&bull;</span>
                    <span>Buka Cepat</span>
                </div>

                <div class="flex items-center gap-2 mt-3.5">
                    <button onclick="window.installSiPintuPwa()" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-black text-xs shadow-md shadow-emerald-700/25 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Pasang Sekarang</span>
                    </button>
                    <button onclick="dismissPwaBanner()" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors cursor-pointer">
                        Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Subtle Floating Mini-Button (Muncul jika banner ditutup agar user tetap bisa pasang sewaktu-waktu) -->
    <div id="pwa-floating-fab" class="hidden fixed bottom-5 right-4 z-40">
        <button onclick="window.installSiPintuPwa()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-emerald-800/90 backdrop-blur-md text-white border border-emerald-500/40 shadow-xl text-xs font-black hover:bg-emerald-800 hover:scale-105 active:scale-95 transition-all cursor-pointer">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span>Pasang App</span>
        </button>
    </div>

    <!-- 3. Universal Interactive Step-by-Step Installation Modal Guide -->
    <div id="pwa-guide-modal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl border border-slate-200 transform transition-all animate-in fade-in zoom-in-95 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('icons/icon-192x192.png') }}" class="w-11 h-11 rounded-2xl object-contain ring-2 ring-emerald-500/30 p-1 bg-emerald-50 shadow-xs">
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-emerald-950">Cara Pasang Aplikasi SiPintu</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Panduan mudah langsung dari browser</p>
                    </div>
                </div>
                <button onclick="closePwaGuideModal()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Device Selector Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl my-4 text-xs font-bold">
                <button type="button" onclick="switchGuideTab('android')" id="tab-btn-android" class="flex-1 py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5 bg-white text-emerald-950 shadow-xs font-extrabold">
                    Android
                </button>
                <button type="button" onclick="switchGuideTab('ios')" id="tab-btn-ios" class="flex-1 py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                    iPhone/iPad
                </button>
                <button type="button" onclick="switchGuideTab('desktop')" id="tab-btn-desktop" class="flex-1 py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                    Laptop/PC
                </button>
            </div>

            <!-- TAB 1: ANDROID CHROME -->
            <div id="guide-content-android" class="space-y-3 text-xs text-slate-700">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">1</span>
                    <div>
                        Ketuk tombol menu <strong>titik tiga (⋮)</strong> di <strong>pojok kanan atas</strong> browser Google Chrome.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">2</span>
                    <div>
                        Pilih menu <strong>"Instal aplikasi"</strong> (atau <strong>"Tambahkan ke Layar Utama"</strong>).
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">3</span>
                    <div>
                        Ketuk <strong>"Instal"</strong> saat pop-up konfirmasi muncul. Selesai! Icon <strong>SiPintu</strong> langsung terpasang di Layar Utama HP Anda.
                    </div>
                </div>
            </div>

            <!-- TAB 2: IOS SAFARI -->
            <div id="guide-content-ios" class="hidden space-y-3 text-xs text-slate-700">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">1</span>
                    <div>
                        Buka website ini di browser <strong>Safari</strong>, lalu ketuk tombol <strong>Bagikan (Share)</strong> <svg class="inline-block w-4 h-4 text-blue-500 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg> di bar bawah layar.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">2</span>
                    <div>
                        Gulir ke bawah dan pilih menu <strong>"Tambahkan ke Layar Utama" (Add to Home Screen)</strong>.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">3</span>
                    <div>
                        Ketuk <strong>"Tambah"</strong> di pojok kanan atas. Icon SiPintu siap digunakan seperti aplikasi App Store!
                    </div>
                </div>
            </div>

            <!-- TAB 3: DESKTOP / PC -->
            <div id="guide-content-desktop" class="hidden space-y-3 text-xs text-slate-700">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">1</span>
                    <div>
                        Pada bilah alamat (URL bar) browser Chrome atau Edge di atas, perhatikan ikon <strong>Instal SiPintu (komputer dengan panah bawah)</strong> di ujung kanan.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">2</span>
                    <div>
                        Ketuk ikon tersebut lalu klik tombol <strong>"Instal"</strong>.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shrink-0 mt-0.5">3</span>
                    <div>
                        Aplikasi SiPintu akan terbuka dalam jendela tersendiri di desktop dan shortcut muncul di Desktop/Start Menu.
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex items-center gap-2">
                <button onclick="closePwaGuideModal()" class="flex-1 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md shadow-emerald-700/20 transition-all cursor-pointer">
                    Saya Mengerti
                </button>
            </div>
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
                        // Registration success
                    })
                    .catch(function (err) {
                        console.warn('Service Worker registration failed:', err);
                    });
            });
        }

        // 2. Standalone Detection (Sudah terpasang dan berjalan sebagai aplikasi)
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true ||
                           document.referrer.includes('android-app://');

        var isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        window.deferredPwaPrompt = null;

        // Auto default tab in guide modal based on device
        window.activePwaTab = isIos ? 'ios' : (isMobile ? 'android' : 'desktop');

        // Capture Chrome's native beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.deferredPwaPrompt = e;

            // Update UI buttons to show direct install ready
            updateInstallButtons(true);

            // If banner was shown, make sure it triggers prompt directly
            var bannerBtn = document.querySelector('#pwa-install-banner button[onclick="window.installSiPintuPwa()"]');
            if (bannerBtn) {
                bannerBtn.classList.add('ring-2', 'ring-emerald-400');
            }
        });

        window.addEventListener('appinstalled', function () {
            window.deferredPwaPrompt = null;
            hidePwaBanner();
            hidePwaFab();
            updateInstallButtons(false);
            if (typeof window.showToastNotification === 'function') {
                window.showToastNotification('success', 'Aplikasi SiPintu berhasil dipasang di Layar Utama!');
            }
        });

        // Universal Install Trigger
        window.installSiPintuPwa = function () {
            // Jika sudah terpasang
            if (isStandalone) {
                if (typeof window.showToastNotification === 'function') {
                    window.showToastNotification('info', 'Aplikasi SiPintu sudah terpasang dan aktif di perangkat Anda.');
                } else {
                    alert('Aplikasi SiPintu sudah terpasang dan aktif di perangkat Anda.');
                }
                return;
            }

            // Jika Chrome native prompt siap -> Buka 1-Click Install Android langsung!
            if (window.deferredPwaPrompt) {
                window.deferredPwaPrompt.prompt();
                window.deferredPwaPrompt.userChoice.then(function (choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        hidePwaBanner();
                        hidePwaFab();
                    }
                    window.deferredPwaPrompt = null;
                });
            } else {
                // Jika prompt belum siap atau browser membutuhkan panduan -> Buka Modal Panduan Visual
                openPwaGuideModal(window.activePwaTab);
            }
        };

        window.dismissPwaBanner = function () {
            localStorage.setItem('sipintu_pwa_dismissed', Date.now().toString());
            hidePwaBanner();
            // Tampilkan floating FAB kecil jika mobile
            if (isMobile && !isStandalone) {
                showPwaFab();
            }
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

        // Modal Guides
        window.openPwaGuideModal = function (tab) {
            var modal = document.getElementById('pwa-guide-modal');
            if (modal) {
                modal.classList.remove('hidden');
                switchGuideTab(tab || window.activePwaTab);
            }
        };

        window.closePwaGuideModal = function () {
            var modal = document.getElementById('pwa-guide-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        };

        window.switchGuideTab = function (tab) {
            window.activePwaTab = tab;
            var tabs = ['android', 'ios', 'desktop'];
            tabs.forEach(function (t) {
                var btn = document.getElementById('tab-btn-' + t);
                var content = document.getElementById('guide-content-' + t);
                if (t === tab) {
                    if (btn) {
                        btn.className = 'flex-1 py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5 bg-white text-emerald-950 shadow-xs font-extrabold';
                    }
                    if (content) content.classList.remove('hidden');
                } else {
                    if (btn) {
                        btn.className = 'flex-1 py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 font-semibold';
                    }
                    if (content) content.classList.add('hidden');
                }
            });
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

        // Auto display banner on mobile after 1.2s if not standalone
        document.addEventListener('DOMContentLoaded', function () {
            updateInstallButtons(true);

            if (!isStandalone) {
                var dismissedAt = localStorage.getItem('sipintu_pwa_dismissed');
                var threeDays = 3 * 24 * 60 * 60 * 1000;
                
                // Jika belum pernah di-dismiss, tampilkan banner setelah 1.2 detik
                if (!dismissedAt || (Date.now() - parseInt(dismissedAt, 10)) > threeDays) {
                    setTimeout(function () {
                        if (!isStandalone) {
                            showPwaBanner();
                        }
                    }, 1200);
                } else if (isMobile) {
                    // Jika baru saja di-dismiss, tetap sediakan floating FAB kecil di pojok
                    showPwaFab();
                }
            }
        });
    })();
</script>
