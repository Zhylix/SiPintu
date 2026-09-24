@if(auth()->check() && auth()->user()->needsSecurityOnboarding())
    @php
        $needsPassword = auth()->user()->needsPasswordChange();
        $needsPhone = auth()->user()->needsWhatsAppPhone();
        $isFirstLoginNotice = session('security_onboarding_notice', false);
    @endphp

    <div x-data="{ 
            open: true,
            dismiss() {
                this.open = false;
                sessionStorage.setItem('sipintu_security_popup_dismissed', 'true');
            },
            init() {
                const wasDismissed = sessionStorage.getItem('sipintu_security_popup_dismissed');
                // Auto-show on first login session or if not dismissed yet
                if (wasDismissed === 'true' && !{{ $isFirstLoginNotice ? 'true' : 'false' }}) {
                    this.open = false;
                }
            }
         }"
         x-cloak>
        
        <!-- Backdrop Overlay -->
        <div class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs transition-opacity"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Pop-up Modal Dialog -->
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
             
            <div class="relative bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-amber-200 text-left overflow-hidden"
                 @click.away="dismiss()">
                 
                <!-- Decorative Glow Accents -->
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-100/70 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-emerald-100/70 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Modal Top Row -->
                <div class="flex items-start justify-between gap-4 relative z-10">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <button type="button" @click="dismiss()"
                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center cursor-pointer"
                            title="Tutup Pengumuman">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Header Text -->
                <div class="mt-4 space-y-1.5 relative z-10">
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                        <span>PENGUMUMAN KEAMANAN AKUN</span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                        Pengamanan Wajib Akun SiPintu
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 font-medium leading-relaxed">
                        Demi keamanan identitas Anda di seluruh ekosistem aplikasi SMKN 1 Bangsri, akun Anda diwajibkan untuk melengkapi pengaturan berikut:
                    </p>
                </div>

                <!-- Checklist Cards -->
                <div class="mt-4 space-y-2.5 p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 text-xs relative z-10">
                    @if($needsPassword)
                    <div class="flex items-start space-x-3">
                        <div class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</div>
                        <div class="space-y-0.5">
                            <div class="font-bold text-slate-900">Ubah Kata Sandi Awal / Bawaan</div>
                            <p class="text-slate-600 text-[11px] leading-relaxed">Akun Anda masih menggunakan kata sandi awal bawaan sistem (<span class="font-mono text-amber-800 font-bold">password</span>). Segera ganti demi mencegah akses tidak sah.</p>
                        </div>
                    </div>
                    @endif

                    @if($needsPhone)
                    <div class="flex items-start space-x-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">{{ $needsPassword ? '2' : '1' }}</div>
                        <div class="space-y-0.5">
                            <div class="font-bold text-slate-900">Lengkapi Nomor WhatsApp Aktif</div>
                            <p class="text-slate-600 text-[11px] leading-relaxed">Dibutuhkan untuk menerima pengumuman resmi sekolah dan fitur pemulihan kata sandi mandiri via kode OTP WhatsApp.</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex flex-col sm:flex-row gap-2.5 relative z-10">
                    <a href="{{ route('profile') }}#{{ $needsPassword ? 'ganti_password' : 'whatsapp' }}"
                       @click="dismiss()"
                       class="flex-1 py-3 px-5 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-extrabold rounded-xl text-xs transition-all shadow-md shadow-emerald-700/25 flex items-center justify-center space-x-2 text-center cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Amankan Akun Sekarang</span>
                    </a>
                    <button type="button" @click="dismiss()"
                            class="py-3 px-5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all cursor-pointer text-center">
                        Nanti Saja
                    </button>
                </div>
            </div>
        </div>

        <!-- Subtle Floating Re-open Badge when Dismissed -->
        <button type="button"
                x-show="!open"
                @click="open = true"
                class="fixed bottom-5 right-5 z-40 px-3.5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-full text-xs font-bold shadow-lg shadow-amber-500/25 flex items-center space-x-2 transition-all transform hover:scale-105 cursor-pointer">
            <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
            <span>Pengumuman Pengamanan Akun</span>
        </button>
    </div>
@endif
