@extends('layouts.app', ['headerTitle' => 'Profil Pengguna'])

@section('content')
<div class="w-full space-y-6" x-data="{ 
    activeSection: '{{ old('active_section', session('active_section', 'profil')) }}',
    avatarModalOpen: {{ $errors->has('avatar') ? 'true' : 'false' }},
    avatarPreview: null,
    avatarError: null,
    isHighlighted: false,
    validSections: ['profil', 'nama_lengkap', 'email', 'whatsapp', 'ganti_password', 'perangkat_login', 'riwayat_login', 'aplikasi_lain'],
    init() {
        const serverSection = '{{ old('active_section', session('active_section', '')) }}';
        const hashSection = window.location.hash.replace('#', '');
        const storedSection = localStorage.getItem('sipintu_profile_active_section');

        let initial = 'profil';
        if (serverSection && this.validSections.includes(serverSection)) {
            initial = serverSection;
        } else if (hashSection && this.validSections.includes(hashSection)) {
            initial = hashSection;
        } else if (storedSection && this.validSections.includes(storedSection)) {
            initial = storedSection;
        }

        if (['nama_lengkap', 'email', 'whatsapp'].includes(initial)) {
            initial = 'profil';
        }

        this.activeSection = initial;
        this.syncState(this.activeSection);

        this.$watch('activeSection', (newSec) => {
            this.syncState(newSec);
        });

        window.addEventListener('hashchange', () => {
            let currentHash = window.location.hash.replace('#', '');
            if (['nama_lengkap', 'email', 'whatsapp'].includes(currentHash)) {
                currentHash = 'profil';
            }
            if (this.validSections.includes(currentHash)) {
                this.activeSection = currentHash;
            }
        });
    },
    syncState(section) {
        if (this.validSections.includes(section)) {
            localStorage.setItem('sipintu_profile_active_section', section);
            if (window.history.replaceState) {
                window.history.replaceState(null, null, '#' + section);
            }
        }
    },
    navigateToSection(section) {
        if (['nama_lengkap', 'email', 'whatsapp'].includes(section)) {
            section = 'profil';
        }
        if (this.validSections.includes(section)) {
            this.activeSection = section;
            this.syncState(section);
            this.$nextTick(() => {
                const target = document.getElementById('profile-content-panel');
                if (target) {
                    const yOffset = -75;
                    const y = target.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                    this.isHighlighted = true;
                    setTimeout(() => { this.isHighlighted = false; }, 1400);
                }
            });
        }
    },
    handleFileChange(event) {
        this.avatarError = null;
        const file = event.target.files[0];
        if (!file) return;

        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            this.avatarError = 'Format file tidak didukung. Pilih foto dengan format JPEG, PNG, atau WEBP.';
            event.target.value = '';
            this.avatarPreview = null;
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            this.avatarError = `Ukuran foto (${sizeMb} MB) melebihi batas maksimal 5 MB. Silakan pilih foto lain.`;
            event.target.value = '';
            this.avatarPreview = null;
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => { this.avatarPreview = e.target.result; };
        reader.readAsDataURL(file);
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- KOLOM KIRI: PROFILE CARD & NAVIGASI -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Card Header User Profile (Bagian Atas) -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm text-center relative overflow-hidden">
                
                <!-- Foto Avatar Profile -->
                <div @click="avatarModalOpen = true" class="relative inline-block mx-auto mb-3 group cursor-pointer" title="Klik untuk mengubah foto profil">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" loading="lazy" decoding="async" class="w-24 h-24 rounded-2xl object-cover ring-4 ring-emerald-500/10 shadow-sm transition-transform duration-300 group-hover:scale-105">
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-3xl shadow-sm ring-4 ring-emerald-500/10">
                            {{ $user->initials() }}
                        </div>
                    @endif
                    <button @click="avatarModalOpen = true" type="button" class="absolute -bottom-2 -right-2 p-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white shadow-md border-2 border-white transition-all hover:scale-110" title="Ubah Foto Profil">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                </div>

                <!-- Nama User -->
                <h2 class="text-xl font-black text-emerald-950 tracking-tight">{{ $user->name }}</h2>
                
                <!-- Username / Identity -->
                <div class="text-xs text-emerald-700 font-bold font-mono mt-0.5">
                    {{ $user->username ? '@'.$user->username : ($user->external_id ? 'ID: '.$user->external_id : $user->email) }}
                </div>

                <!-- Siswa / Guru / DUDI Badge -->
                <div class="mt-2.5 inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-50 text-emerald-800 border border-emerald-200">
                    {{ $user->getUserTypeName() }} {{ $user->classroom ? '• '.$user->classroom : '' }}
                </div>

                <!-- Ringkasan Info Kontak Cepat -->
                <div class="mt-3.5 pt-3.5 border-t border-slate-100 flex flex-wrap items-center justify-center gap-2 text-xs">
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/80 text-slate-600 font-medium max-w-full">
                        <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate max-w-[180px]">{{ $user->email }}</span>
                    </div>
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/80 text-slate-600 font-medium">
                        <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>{{ $user->phone ?: 'Belum ada telepon' }}</span>
                    </div>
                </div>

                <!-- Tombol Aksi Langsung di Bagian Atas Card -->
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-center gap-2.5">
                    <button @click="navigateToSection('profil')" type="button" class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white text-xs font-black shadow-md shadow-emerald-700/20 flex items-center justify-center space-x-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Ubah Profil</span>
                    </button>
                    <button @click="navigateToSection('ganti_password')" type="button" class="py-2.5 px-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 text-xs font-extrabold border border-slate-200 flex items-center justify-center space-x-1.5 transition-all cursor-pointer" title="Ganti Kata Sandi">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Sandi</span>
                    </button>
                </div>
            </div>

            <!-- TAB NAVIGASI HORIZONTAL MOBILE (< lg) -->
            <!-- Tampil langsung di bawah Card Profil agar form langsung terlihat di bawahnya tanpa terdorong jauh -->
            <div class="block lg:hidden bg-white p-2 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center gap-1.5 overflow-x-auto py-0.5">
                    <button @click="navigateToSection('profil')" type="button" 
                        :class="activeSection === 'profil' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="shrink-0 px-3 py-2 rounded-xl text-xs font-black flex items-center space-x-1.5 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Data Profil</span>
                    </button>

                    <button @click="navigateToSection('ganti_password')" type="button" 
                        :class="activeSection === 'ganti_password' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="shrink-0 px-3 py-2 rounded-xl text-xs font-black flex items-center space-x-1.5 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Sandi & WhatsApp</span>
                    </button>

                    <button @click="navigateToSection('perangkat_login')" type="button" 
                        :class="activeSection === 'perangkat_login' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="shrink-0 px-3 py-2 rounded-xl text-xs font-black flex items-center space-x-1.5 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Perangkat</span>
                    </button>

                    <button @click="navigateToSection('riwayat_login')" type="button" 
                        :class="activeSection === 'riwayat_login' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="shrink-0 px-3 py-2 rounded-xl text-xs font-black flex items-center space-x-1.5 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Riwayat</span>
                    </button>

                    <button @click="navigateToSection('aplikasi_lain')" type="button" 
                        :class="activeSection === 'aplikasi_lain' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        class="shrink-0 px-3 py-2 rounded-xl text-xs font-black flex items-center space-x-1.5 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Aplikasi ({{ count($accessibleApps ?? []) }} )</span>
                    </button>
                </div>
            </div>

            <!-- MENU SIDEBAR DESKTOP (Tampil di Layar Desktop lg) -->
            <div class="hidden lg:block bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
                
                <!-- GROUP 1: Informasi Pribadi -->
                <div class="p-4 space-y-1">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Informasi Pribadi</div>
                    
                    <button @click="avatarModalOpen = true" type="button" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between text-slate-700 hover:bg-slate-50 font-bold transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span>Foto Profil</span>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Ubah</span>
                    </button>
                    
                    <button @click="navigateToSection('profil')" :class="activeSection === 'profil' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <span>Data Profil & Kontak</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- GROUP 2: Keamanan -->
                <div class="p-4 space-y-1">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Keamanan Akun</div>
                    
                    <button @click="navigateToSection('ganti_password')" :class="activeSection === 'ganti_password' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <span>Sandi & WhatsApp</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="navigateToSection('perangkat_login')" :class="activeSection === 'perangkat_login' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <span>Perangkat Login</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="navigateToSection('riwayat_login')" :class="activeSection === 'riwayat_login' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span>Riwayat Login</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- GROUP 3: Aplikasi & Perangkat -->
                <div class="p-4 space-y-2">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Aplikasi & Perangkat</div>

                    <!-- Tombol Instal Aplikasi PWA -->
                    <button type="button" onclick="window.installSiPintuPwa(this)" class="w-full px-3.5 py-2.5 rounded-2xl text-xs flex items-center justify-between text-slate-700 hover:bg-emerald-50/80 font-bold transition-all group cursor-pointer border border-emerald-100 bg-emerald-50/30 shadow-2xs">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-xl bg-emerald-700 text-white shrink-0 shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </div>
                            <div class="text-left">
                                <span class="block text-emerald-950 font-black">Unduh & Instal Aplikasi</span>
                                <span class="block text-[10px] text-slate-500 font-medium">Pasang di Layar Utama HP</span>
                            </div>
                        </div>
                        <span id="profile-pwa-badge" class="px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Tersedia
                        </span>
                    </button>

                    <button @click="navigateToSection('aplikasi_lain')" :class="activeSection === 'aplikasi_lain' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <span>Aplikasi Terdaftar</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800">{{ count($accessibleApps ?? []) }}</span>
                    </button>
                </div>

                <!-- GROUP 4: Keluar -->
                <div class="p-4">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-3.5 py-3 rounded-xl text-xs font-black text-rose-600 hover:bg-rose-50 flex items-center justify-between transition-all group border border-rose-100">
                            <div class="flex items-center space-x-3">
                                <span>Keluar</span>
                            </div>
                            <svg class="w-4 h-4 text-rose-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- DETAIL PANEL KANAN (KONTEN AKTIF) -->
        <div id="profile-content-panel" class="lg:col-span-8 scroll-mt-20">
            
            <!-- SECTION UTAMA: Data Profil & Kontak Lengkap -->
            <div x-show="activeSection === 'profil' || activeSection === 'nama_lengkap' || activeSection === 'email' || activeSection === 'whatsapp'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 :class="{'ring-2 ring-emerald-500 shadow-lg': isHighlighted}"
                 class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6 transition-all duration-300">
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 gap-2">
                    <div>
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                            <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            <span>Formulir Ubah Profil</span>
                        </div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <span>Informasi Data Diri & Kontak Akun</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Ubah nama resmi, email login, dan nomor kontak yang terdaftar di SiPintu Gateway.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_section" value="profil">
                    <input type="hidden" name="username" value="{{ $user->username }}">

                    <!-- Card Bagian 1: Identitas Resmi -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                        <div class="flex items-center space-x-2 pb-2 border-b border-slate-200/60">
                            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-800">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-wide">Identitas Akun</span>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Nama Lengkap Resmi <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm font-bold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all shadow-2xs">
                            <p class="text-[11px] text-slate-500 font-medium mt-1">Nama ini digunakan pada sertifikat, laporan, dan otentikasi seluruh sistem SSO sekolah.</p>
                            @error('name')
                                <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($user->isStudent() || $user->isAlumni() || $user->classroom)
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Kelas / Rombel Terdaftar</label>
                            <input type="text" value="{{ $user->classroom ?? ($user->isAlumni() ? 'Alumni (Telah Lulus)' : 'Belum Ada Kelas') }}" readonly disabled
                                class="w-full px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 text-sm font-bold cursor-not-allowed">
                            <p class="text-[11px] text-slate-500 font-medium mt-1">Status kelas disinkronkan secara otomatis dari SIJUNA / Dapodik.</p>
                        </div>
                        @endif

                        @if($user->isAlumni())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Tahun Masuk (Siswa Baru)</label>
                                <input type="text" value="{{ $user->tahun_masuk ? $user->tahun_masuk . ' (' . $user->tahun_masuk_tanggal . ')' : '-' }}" readonly disabled
                                    class="w-full px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 text-emerald-900 font-mono text-sm font-bold cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Tahun Lulus (Alumni)</label>
                                <input type="text" value="{{ $user->tahun_lulus ? $user->tahun_lulus . ' (' . $user->tahun_lulus_tanggal . ')' : '-' }}" readonly disabled
                                    class="w-full px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 text-teal-900 font-mono text-sm font-bold cursor-not-allowed">
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Card Bagian 2: Kontak & Notifikasi WhatsApp -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                        <div class="flex items-center space-x-2 pb-2 border-b border-slate-200/60">
                            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-800">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-wide">Kontak & Notifikasi</span>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Alamat Email Login <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm font-bold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all shadow-2xs">
                            <p class="text-[11px] text-slate-500 font-medium mt-1">Digunakan untuk login SSO, reset password, dan menerima notifikasi sistem.</p>
                            @error('email')
                                <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp Aktif</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890"
                                class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm font-bold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all shadow-2xs">
                            <p class="text-[11px] text-slate-500 font-medium mt-1">Nomor aktif untuk menerima pengumuman penting sekolah langsung melalui WhatsApp.</p>
                            @error('phone')
                                <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Toggle Preferensi Notifikasi WhatsApp -->
                        <div class="p-3.5 rounded-xl bg-white border border-slate-200 flex items-center justify-between gap-3 shadow-2xs">
                            <div class="space-y-0.5">
                                <label for="wa_notify_toggle" class="text-xs font-black text-slate-900 cursor-pointer block">Terima Notifikasi Pengumuman WhatsApp</label>
                                <p class="text-[11px] text-slate-500 font-medium">Kirimkan broadcast pengumuman resmi SMKN 1 Bangsri langsung ke WhatsApp nomor di atas.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="hidden" name="wa_notify" value="0">
                                <input type="checkbox" id="wa_notify_toggle" name="wa_notify" value="1" {{ old('wa_notify', $user->wa_notify) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Tombol Simpan Perubahan Profil -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-emerald-700/25 flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Simpan Perubahan Profil</span>
                        </button>
                        <span class="text-[11px] text-slate-400 font-medium text-center sm:text-right">Perubahan langsung tersinkron ke semua layanan SSO.</span>
                    </div>
                </form>
            </div>

            <!-- SECTION 4: Ganti Password & Nomor WhatsApp -->
            <div x-show="activeSection === 'ganti_password'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 :class="{'ring-2 ring-emerald-500/20 shadow-lg': isHighlighted}"
                 class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-6 transition-all duration-300"
                 x-data="{
                     showCurrent: false,
                     showNew: false,
                     showConfirm: false
                 }">
                
                <!-- Minimalist Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-5 border-b border-slate-100 gap-3">
                    <div class="space-y-1">
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Keamanan & Kontak Terpadu</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span>Ganti Kata Sandi & Nomor WhatsApp</span>
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Perbarui kata sandi login dan nomor WhatsApp aktif. Data akan langsung disinkronkan ke seluruh aplikasi downstream.</p>
                    </div>

                    <!-- Sync Status Pill -->
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/70 text-slate-600 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Real-Time Sync Aktif</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_section" value="ganti_password">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column: Password Fields -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Kredensial Kata Sandi</span>
                            </div>

                            <!-- Kata Sandi Saat Ini -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input :type="showCurrent ? 'text' : 'password'" name="current_password" required
                                        placeholder="Ketik kata sandi saat ini"
                                        class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-medium focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition-all">
                                    <button type="button" @click="showCurrent = !showCurrent"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                        <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kata Sandi Baru -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input :type="showNew ? 'text' : 'password'" name="password" required minlength="8"
                                        placeholder="Minimal 8 karakter baru"
                                        class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-medium focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition-all">
                                    <button type="button" @click="showNew = !showNew"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                        <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 font-medium">Minimal 8 karakter (disarankan kombinasi huruf dan angka).</p>
                                @error('password')
                                    <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Konfirmasi Kata Sandi Baru -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required minlength="8"
                                        placeholder="Ketik ulang kata sandi baru"
                                        class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-medium focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition-all">
                                    <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: WhatsApp & Downstream Sync Info -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Kontak WhatsApp Aktif</span>
                            </div>

                            <!-- Nomor WhatsApp Input -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp Aktif <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-600">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm0 18.17c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 01-1.26-4.4c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 012.41 5.83c.02 4.54-3.68 8.25-8.23 8.25zm4.52-6.17c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.37-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.15.17-.25.25-.42.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.84-.86 2.05s.88 2.38 1 2.55c.12.17 1.74 2.65 4.21 3.72.59.25 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.23-.17-.48-.3z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required
                                        placeholder="Contoh: 081234567890"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 focus:outline-none transition-all">
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1 font-medium">Nomor WhatsApp aktif untuk kode OTP pemulihan akun & notifikasi sekolah.</p>
                                @error('phone')
                                    <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Minimalist Downstream Info Card -->
                            <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100 text-slate-700 text-xs space-y-2">
                                <div class="flex items-center gap-2 font-bold text-emerald-900">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Penyelarasan Real-Time Downstream</span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-relaxed">
                                    Ketika Anda menyimpan formulir ini, kata sandi baru dan nomor WhatsApp Anda akan <strong>langsung dikirimkan otomatis ke seluruh aplikasi downstream</strong> yang terhubung (CBT, Perpustakaan, dsb).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Minimalist Footer Actions -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan & Sinkronkan Akun</span>
                        </button>
                        <span class="text-[11px] text-slate-400 font-medium text-center sm:text-right">
                            Kredensial baru langsung aktif seketika tanpa perlu login ulang.
                        </span>
                    </div>
                </form>
            </div>

            <!-- SECTION 5: Perangkat Login -->
            <div x-show="activeSection === 'perangkat_login'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 :class="{'ring-2 ring-emerald-500 shadow-lg': isHighlighted}"
                 class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6 transition-all duration-300">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                            <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Status Sesi Login</span>
                        </div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <span>Perangkat Active Login Saat Ini</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Informasi perangkat & IP address yang sedang terhubung dengan sesi Anda saat ini.</p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 rounded-xl bg-emerald-700 text-white shadow-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-black text-sm text-slate-900">Browser Sesi Ini</h4>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ request()->userAgent() }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Aktif
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-200 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium block">IP Address Sesi:</span>
                            <span class="font-mono font-bold text-slate-900">{{ request()->ip() }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block">Status Keamanan:</span>
                            <span class="font-extrabold text-emerald-700">TERAUTENTIKASI</span>
                        </div>
                    </div>

                    <!-- Form Logout dari Semua Perangkat Lain -->
                    <div class="pt-4 border-t border-slate-200" x-data="{ openLogoutOther: false }">
                        <button type="button" @click="openLogoutOther = !openLogoutOther"
                                class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all flex items-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Keluarkan Akun dari Semua Perangkat Lain</span>
                        </button>

                        <div x-show="openLogoutOther" x-transition class="mt-3 p-4 rounded-xl bg-white border border-rose-200 space-y-2.5 shadow-xs">
                            <p class="text-xs text-slate-600 font-medium">
                                Masukkan kata sandi akun Anda untuk memvalidasi dan memutus semua sesi login aktif di komputer, ponsel, atau browser lain.
                            </p>
                            <form method="POST" action="{{ route('profile.logout-other-devices') }}" class="flex flex-col sm:flex-row gap-2">
                                @csrf
                                <input type="password" name="password" required placeholder="Kata sandi akun Anda"
                                    class="flex-1 px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-semibold focus:outline-none focus:border-rose-600">
                                <button type="submit"
                                    class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-extrabold transition-all shadow-xs cursor-pointer whitespace-nowrap">
                                    Logout Perangkat Lain
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: Riwayat Login -->
            <div x-show="activeSection === 'riwayat_login'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 :class="{'ring-2 ring-emerald-500 shadow-lg': isHighlighted}"
                 class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6 transition-all duration-300">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                            <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Aktivitas Keamanan</span>
                        </div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <span>Riwayat Login & Aktivitas Keamanan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Catatan log aktivitas masuk (login) dan keamanan akun Anda.</p>
                    </div>
                </div>

                @if(count($auditLogs) > 0)
                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        @foreach($auditLogs as $log)
                            @php
                                $isSsoFail = $log->isSsoFailure();
                            @endphp
                            <div class="p-4 rounded-2xl border flex items-start justify-between gap-4 text-xs {{ $isSsoFail ? 'bg-rose-50/70 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 rounded-xl shrink-0 mt-0.5 {{ $isSsoFail ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        @if($isSsoFail)
                                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-extrabold flex items-center gap-2 {{ $isSsoFail ? 'text-rose-900' : 'text-slate-900' }}">
                                            @if($isSsoFail)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300">GAGAL SSO</span>
                                            @endif
                                            <span>{{ str_replace('_', ' ', strtoupper($log->activity)) }}</span>
                                            <code class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-bold">{{ $log->ip_address }}</code>
                                        </div>
                                        <p class="text-[11px] text-slate-500 font-medium truncate max-w-sm mt-1" title="{{ $log->user_agent }}">
                                            {{ Str::limit($log->user_agent, 60) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right text-[11px] text-slate-400 font-semibold shrink-0">
                                    {{ $log->created_at?->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400 text-xs font-medium">
                        Belum ada data riwayat aktivitas login.
                    </div>
                @endif
            </div>

            <!-- SECTION 8: Aplikasi Lain -->
            <div x-show="activeSection === 'aplikasi_lain'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 :class="{'ring-2 ring-emerald-500 shadow-lg': isHighlighted}"
                 class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6 transition-all duration-300">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                            <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Layanan Single Sign-On</span>
                        </div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <span>Aplikasi Terhubung SSO</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Daftar layanan aplikasi sekolah terotorisasi yang dapat diakses dengan akun SiPintu Anda.</p>
                    </div>
                </div>

                @if(count($accessibleApps) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($accessibleApps as $app)
                            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-emerald-300 hover:shadow-lg transition-all duration-300 flex flex-col justify-between group">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-base group-hover:scale-110 transition-transform">
                                            {{ strtoupper(substr($app->name, 0, 2)) }}
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            {{ $app->category?->name ?? 'Aplikasi' }}
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="font-black text-sm text-emerald-950 group-hover:text-emerald-700 transition-colors">{{ $app->name }}</h4>
                                        <p class="text-xs text-slate-600 line-clamp-2 mt-1 font-medium">{{ $app->description ?: 'Layanan sistem aplikasi SSO SMKN 1 Bangsri.' }}</p>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-200/80 mt-3 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400 font-mono">Client ID: {{ Str::limit($app->client_id, 10) }}</span>
                                    <a href="{{ route('demo.login', $app->slug) }}" class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg transition-all shadow-xs flex items-center space-x-1">
                                        <span>Buka SSO</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400 text-xs font-medium">
                        Belum ada aplikasi SSO terhubung untuk peran Anda.
                    </div>
                @endif
            </div>

            <!-- CARD TAMBAHAN KHUSUS MOBILE: Unduh PWA & Keluar -->
            <div class="block lg:hidden mt-6 bg-white rounded-3xl border border-slate-200 p-4 shadow-sm space-y-3">
                <button type="button" onclick="window.installSiPintuPwa(this)" class="w-full px-3.5 py-2.5 rounded-2xl text-xs flex items-center justify-between text-slate-700 hover:bg-emerald-50/80 font-bold transition-all group cursor-pointer border border-emerald-100 bg-emerald-50/30">
                    <div class="flex items-center space-x-3">
                        <div class="p-1.5 rounded-xl bg-emerald-700 text-white shrink-0 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-emerald-950 font-black">Unduh & Instal Aplikasi</span>
                            <span class="block text-[10px] text-slate-500 font-medium">Pasang di Layar Utama HP</span>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Tersedia
                    </span>
                </button>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-3.5 py-2.5 rounded-xl text-xs font-black text-rose-600 hover:bg-rose-50 flex items-center justify-between transition-all group border border-rose-100">
                        <div class="flex items-center space-x-3">
                            <span>Keluar dari Akun</span>
                        </div>
                        <svg class="w-4 h-4 text-rose-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>

        </div>

    </div>

    <!-- MODAL FOTO PROFIL UPLOAD -->
    <div x-show="avatarModalOpen" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        
        <div @click.away="avatarModalOpen = false" class="w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl space-y-5 border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h4 class="font-black text-base text-emerald-950">Kelola Foto Profil</h4>
                <button @click="avatarModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body Image Preview -->
            <div class="flex flex-col items-center justify-center space-y-4">
                <template x-if="avatarPreview">
                    <img :src="avatarPreview" class="w-32 h-32 rounded-2xl object-cover ring-4 ring-emerald-500/30 shadow-xl">
                </template>
                <template x-if="!avatarPreview">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" loading="lazy" decoding="async" class="w-32 h-32 rounded-2xl object-cover ring-4 ring-emerald-500/30 shadow-xl">
                    @else
                        <div class="w-32 h-32 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white flex items-center justify-center font-black text-4xl shadow-xl">
                            {{ $user->initials() }}
                        </div>
                    @endif
                </template>

                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="w-full space-y-4">
                    @csrf
                    <div>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" @change="handleFileChange($event)" required
                            class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 file:cursor-pointer transition-all">
                        <p class="text-[10px] text-emerald-700 mt-1.5 text-center font-semibold">Maksimal 5 MB. Otomatis dikompres & di-crop WebP (Super Ringan & Cepat)</p>
                        <div x-show="avatarError" x-text="avatarError" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold mt-2 text-center" style="display: none;"></div>
                        @error('avatar')
                            <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold mt-2 text-center">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md">
                            Unggah Foto
                        </button>
                        <button type="button" @click="avatarModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                            Batal
                        </button>
                    </div>
                </form>

                @if($user->avatar)
                    <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="w-full pt-2 border-t border-slate-100">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-extrabold rounded-xl transition-all border border-rose-200">
                            Hapus Foto
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
