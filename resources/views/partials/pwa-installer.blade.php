<script>
    (function () {
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true ||
                           document.referrer.includes('android-app://');

        // Deteksi apakah perangkat adalah mobile (smartphone atau tablet)
        function isMobileDevice() {
            var ua = navigator.userAgent || navigator.vendor || window.opera || '';
            var isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|mobile|CriOS/i.test(ua);
            var isIPadOS = (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            var isSmallTouch = (('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 1024);
            return isMobileUA || isIPadOS || isSmallTouch;
        }

        function getPrompt() {
            return window.deferredPWAInstallPrompt || window.deferredPwaPrompt || null;
        }

        window.addEventListener('pwa-prompt-ready', function () {
            updateInstallButtonState();
        });

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.deferredPWAInstallPrompt = e;
            window.deferredPwaPrompt = e;
            updateInstallButtonState();
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

        // Event saat aplikasi selesai dipasang ke Layar Utama
        window.addEventListener('appinstalled', function () {
            window.deferredPWAInstallPrompt = null;
            window.deferredPwaPrompt = null;
            updateInstallButtonState();
            pwaNotify('success', 'Aplikasi SiPintu berhasil dipasang di Layar Utama HP Anda!', 'SiPintu Terpasang');
        });

        // Eksekusi Pemicu Native 
        function triggerNativePrompt(promptEvent, onComplete) {
            try {
                promptEvent.prompt();
                promptEvent.userChoice.then(function (choiceResult) {
                    if (choiceResult && choiceResult.outcome === 'accepted') {
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

        // Pemicu Klik Pasang Universal
        window.installSiPintuPwa = function (btnElement) {
            // Cegah pemasangan di Desktop
            if (!isMobileDevice()) {
                pwaNotify('info', 'Pemasangan aplikasi SiPintu Mobile hanya tersedia di perangkat smartphone / tablet (HP).', 'SiPintu Mobile');
                return;
            }

            if (isStandalone) {
                pwaNotify('info', 'SiPintu Mobile sudah terpasang dan aktif di perangkat Anda.', 'SiPintu');
                return;
            }

            var prompt = getPrompt();

            if (prompt) {
                triggerNativePrompt(prompt);
                return;
            }

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

        function updateInstallButtonState() {
            var isMobile = isMobileDevice();
            var buttons = document.querySelectorAll('[data-pwa-install-btn]');
            buttons.forEach(function (btn) {
                if (!isMobile || isStandalone) {
                    btn.classList.add('hidden');
                    btn.style.setProperty('display', 'none', 'important');
                } else {
                    btn.classList.remove('hidden');
                    btn.style.removeProperty('display');
                }
            });

            var profileBadges = document.querySelectorAll('#profile-pwa-badge, [data-pwa-badge]');
            profileBadges.forEach(function (badge) {
                if (isStandalone) {
                    badge.innerText = '✓ Terpasang';
                    badge.className = 'px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300';
                } else {
                    badge.innerText = 'Tersedia';
                    badge.className = 'px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200';
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateInstallButtonState);
        } else {
            updateInstallButtonState();
        }

        window.addEventListener('resize', updateInstallButtonState);
        window.addEventListener('orientationchange', updateInstallButtonState);
    })();
</script>
