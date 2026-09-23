<!-- SiPintu PWA Direct Installer & Fallback Guide Component -->
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

    <!-- 3. Elegant Fallback Installation Guide Modal (Like app.tasenadev.com) -->
    <div id="pwa-guide-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closePwaGuideModal()"></div>

            <div class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 tracking-tight" id="modal-title">Panduan Pasang Aplikasi</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Pasang SiPintu di Layar Utama HP Anda</p>
                        </div>
                    </div>
                    <button onclick="closePwaGuideModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3.5 text-xs text-slate-600">
                    <!-- Tab Android -->
                    <div class="p-3.5 bg-emerald-50/60 border border-emerald-200/70 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2 font-bold text-emerald-950">
                            <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5902 8.4116 13.8533 8.0818 12 8.0818c-1.8533 0-3.5902.3298-5.1368.8679L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"></path></svg>
                            <span>Pengguna Android (Chrome / Brave / Edge)</span>
                        </div>
                        <ol class="list-decimal list-inside space-y-1.5 pl-1 leading-relaxed text-slate-700 font-medium text-[11px]">
                            <li>Ketuk tombol menu titik tiga (<strong class="text-emerald-900">⋮</strong>) di pojok kanan atas browser.</li>
                            <li>Pilih menu <strong class="text-emerald-900">"Instal aplikasi"</strong> atau <strong class="text-emerald-900">"Tambahkan ke Layar Utama"</strong>.</li>
                            <li>Ketuk <strong class="text-emerald-900">"Instal"</strong> pada konfirmasi yang muncul. Ikon SiPintu akan langsung aktif di beranda HP Anda!</li>
                        </ol>
                    </div>

                    <!-- Tab iPhone / iPad -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                        <div class="flex items-center gap-2 font-bold text-slate-900">
                            <svg class="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.82c.64-.78 1.08-1.87.96-2.96-.93.04-2.07.62-2.73 1.4-.58.68-1.1 1.78-.96 2.85 1.05.08 2.09-.51 2.73-1.29z"></path></svg>
                            <span>Pengguna iPhone / iPad (Safari)</span>
                        </div>
                        <ol class="list-decimal list-inside space-y-1.5 pl-1 leading-relaxed text-slate-700 font-medium text-[11px]">
                            <li>Buka halaman ini menggunakan browser <strong class="text-slate-900">Safari</strong>.</li>
                            <li>Ketuk tombol <strong class="text-slate-900">Bagikan (Share)</strong> ikon kotak dengan panah ke atas di bagian bawah layar.</li>
                            <li>Gulir ke bawah dan ketuk opsi <strong class="text-slate-900">"Tambah ke Layar Utama" (Add to Home Screen)</strong>.</li>
                            <li>Ketuk <strong class="text-slate-900">"Tambah" (Add)</strong> di pojok kanan atas.</li>
                        </ol>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="button" onclick="closePwaGuideModal()" class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md shadow-emerald-700/20 transition-all cursor-pointer">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
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

        // Cek jika prompt sudah tertangkap sebelumnya di head
        if (getPrompt()) {
            showInstallUi();
        }

        // Subscribe ke event
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
                console.log('[PWA ' + type + ']: ' + message);
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

        // Universal Direct Install Trigger (Synchronous User Gesture for 1-Click Install)
        window.installSiPintuPwa = function (btnElement) {
            if (isStandalone) {
                pwaNotify('info', 'Aplikasi SiPintu sudah terpasang dan aktif di perangkat Anda.', 'SiPintu');
                return;
            }

            // In-app webview warning (WhatsApp, Instagram, etc)
            var isWebView = /(wv|FBAN|FBAV|Instagram|Line|WhatsApp)/i.test(navigator.userAgent);
            if (isWebView) {
                pwaNotify('warning', 'Silakan buka tautan SiPintu langsung di aplikasi Google Chrome untuk memasang.', 'Buka di Chrome');
                return;
            }

            var promptEvent = getPrompt();

            // 1. Jika native prompt Android siap, PICU LANGSUNG SEKETIKA (1-KLIK)
            if (promptEvent) {
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
                    }).catch(function (err) {
                        console.warn('Install choice error:', err);
                        window.openPwaGuideModal();
                    });
                } catch (e) {
                    console.warn('Direct prompt exception:', e);
                    window.openPwaGuideModal();
                }
                return;
            }

            // 2. Jika prompt belum siap / iOS / Chrome cooldown -> Tampilkan Modal Panduan Interaktif
            window.openPwaGuideModal();
        };

        window.openPwaGuideModal = function () {
            var modal = document.getElementById('pwa-guide-modal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        };

        window.closePwaGuideModal = function () {
            var modal = document.getElementById('pwa-guide-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
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
