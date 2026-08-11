@extends('layouts.app', ['headerTitle' => 'Profil Pengguna'])

@section('content')
<div class="w-full space-y-6" x-data="{ 
    activeSection: '{{ old('active_section', session('active_section', 'nama_lengkap')) }}',
    avatarModalOpen: false,
    avatarPreview: null,
    validSections: ['nama_lengkap', 'email', 'whatsapp', 'ganti_password', 'perangkat_login', 'riwayat_login', 'aplikasi_lain'],
    init() {
        const serverSection = '{{ old('active_section', session('active_section', '')) }}';
        const hashSection = window.location.hash.replace('#', '');
        const storedSection = localStorage.getItem('sipintu_profile_active_section');

        if (serverSection && this.validSections.includes(serverSection)) {
            this.activeSection = serverSection;
        } else if (hashSection && this.validSections.includes(hashSection)) {
            this.activeSection = hashSection;
        } else if (storedSection && this.validSections.includes(storedSection)) {
            this.activeSection = storedSection;
        } else {
            this.activeSection = 'nama_lengkap';
        }

        this.syncState(this.activeSection);

        this.$watch('activeSection', (newSec) => {
            this.syncState(newSec);
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
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
    handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => { this.avatarPreview = e.target.result; };
            reader.readAsDataURL(file);
        }
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- ========================================== -->
        <!-- MENU NAVIGASI KIRI (SESUAI WIREFRAME USER) -->
        <!-- ========================================== -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Card Header User Profile Header (Clean Light Theme) -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm text-center relative overflow-hidden">
                
                <!-- [ FOTO ] Avatar Profile -->
                <div class="relative inline-block mx-auto mb-3 group">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-2xl object-cover ring-4 ring-emerald-500/10 shadow-sm transition-transform duration-300 group-hover:scale-105">
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
                    {{ $user->getUserTypeName() }}
                </div>
            </div>

            <!-- Grouped Menu Card -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
                
                <!-- GROUP 1: Informasi Pribadi -->
                <div class="p-4 space-y-1">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Informasi Pribadi</div>
                    
                    <button @click="activeSection = 'nama_lengkap'" :class="activeSection === 'nama_lengkap' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <span>Nama Lengkap</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="activeSection = 'email'" :class="activeSection === 'email' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <span>Email</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="activeSection = 'whatsapp'" :class="activeSection === 'whatsapp' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <span>No. WhatsApp</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- GROUP 2: Keamanan -->
                <div class="p-4 space-y-1">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Keamanan</div>
                    
                    <button @click="activeSection = 'ganti_password'" :class="activeSection === 'ganti_password' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <span>Ganti Password</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="activeSection = 'perangkat_login'" :class="activeSection === 'perangkat_login' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <span>Perangkat Login</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button @click="activeSection = 'riwayat_login'" :class="activeSection === 'riwayat_login' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
                        <div class="flex items-center space-x-3">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span>Riwayat Login</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- GROUP 3: Aplikasi Terhubung -->
                <div class="p-4 space-y-1">
                    <div class="px-3 py-1 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Aplikasi Terhubung</div>

                    <button @click="activeSection = 'aplikasi_lain'" :class="activeSection === 'aplikasi_lain' ? 'bg-emerald-50 text-emerald-900 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'" class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all group">
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

        <!-- ========================================== -->
        <!-- DETAIL PANEL KANAN (KONTEN AKTIF)          -->
        <!-- ========================================== -->
        <div class="lg:col-span-8">
            
            <!-- SECTION 1: Nama Lengkap -->
            <div x-show="activeSection === 'nama_lengkap'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>Informasi Nama Lengkap</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Ubah nama resmi yang terdaftar dalam sistem SiPintu Identity Gateway.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_section" value="nama_lengkap">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="phone" value="{{ $user->phone }}">
                    <input type="hidden" name="username" value="{{ $user->username }}">

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-2">Nama Lengkap Resmi <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-bold focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all">
                        @error('name')
                            <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs text-emerald-950 font-medium">
                        Nama lengkap jangan diganti
                    </div>

                    <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Simpan</span>
                    </button>
                </form>
            </div>

            <!-- SECTION 2: Email -->
            <div x-show="activeSection === 'email'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>Alamat Email Utama</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Email ini digunakan untuk autentikasi SSO, pemulihan akun, dan pemberitahuan sistem.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_section" value="email">
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="phone" value="{{ $user->phone }}">
                    <input type="hidden" name="username" value="{{ $user->username }}">

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-2">Alamat Email Terdaftar <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" disabled
                            class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-bold focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all">
                        @error('email')
                            <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Simpan Perubahan Email</span>
                    </button>
                </form>
            </div>

            <!-- SECTION 3: No. WhatsApp -->
            <div x-show="activeSection === 'whatsapp'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>Nomor Telepon & WhatsApp</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Kelola nomor kontak aktif untuk menerima notifikasi pengumuman sekolah via WhatsApp.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_section" value="whatsapp">
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="username" value="{{ $user->username }}">

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-2">Nomor Telepon / WhatsApp Active</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890"
                            class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-bold focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition-all">
                        @error('phone')
                            <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Toggle Preferensi Notifikasi WhatsApp -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div class="space-y-0.5">
                            <label for="wa_notify_toggle" class="text-xs font-black text-slate-900 cursor-pointer">Terima Notifikasi Pengumuman WhatsApp</label>
                            <p class="text-[11px] text-slate-500 font-medium">Aktifkan untuk menerima pengumuman penting sekolah langsung di WhatsApp Anda.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="wa_notify" value="0">
                            <input type="checkbox" id="wa_notify_toggle" name="wa_notify" value="1" {{ old('wa_notify', $user->wa_notify) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Simpan Pengaturan WhatsApp</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- SECTION 4: Ganti Password -->
            <div x-show="activeSection === 'ganti_password'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Ganti Kata Sandi</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Perbarui kata sandi secara berkala untuk perlindungan maksimal akun Anda.</p>
                    </div>
                </div>

                @if($user->isStudent())
                    <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 font-medium space-y-2">
                        <div class="font-black text-emerald-950 flex items-center space-x-2 text-sm">
                            <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Akun Siswa Tersinkron Sijuna</span>
                        </div>
                        <p class="leading-relaxed text-slate-700">Kata sandi untuk akun Siswa bersumber langsung dari SIJUNA API untuk menjaga konsistensi Single Sign-On seluruh aplikasi SMKN 1 Bangsri.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="active_section" value="ganti_password">

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                            <input type="password" name="current_password" required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
                            @error('current_password')
                                <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
                            <p class="text-[10px] text-slate-400 mt-1 font-medium">Minimal 8 karakter kombinasi huruf dan angka.</p>
                            @error('password')
                                <p class="text-xs text-rose-500 font-bold mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                            <input type="password" name="password_confirmation" required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
                        </div>

                        <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                            Perbarui Kata Sandi
                        </button>
                    </form>
                @endif
            </div>

            <!-- SECTION 5: Perangkat Login -->
            <div x-show="activeSection === 'perangkat_login'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
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
                            Aktif Sekarang
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-200 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium block">IP Address Sesi:</span>
                            <span class="font-mono font-bold text-slate-900">{{ request()->ip() }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium block">Status Keamanan:</span>
                            <span class="font-extrabold text-emerald-700">TERAUTENTIKASI SSO</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: Riwayat Login -->
            <div x-show="activeSection === 'riwayat_login'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Riwayat Login & Aktivitas Keamanan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Catatan log aktivitas masuk (login) dan keamanan akun Anda.</p>
                    </div>
                </div>

                @if(count($auditLogs) > 0)
                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        @foreach($auditLogs as $log)
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start justify-between gap-4 text-xs">
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 rounded-xl bg-emerald-100 text-emerald-800 shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 flex items-center gap-2">
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
            <div x-show="activeSection === 'aplikasi_lain'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black text-emerald-950 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
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
                        <img src="{{ $user->avatar_url }}" class="w-32 h-32 rounded-2xl object-cover ring-4 ring-emerald-500/30 shadow-xl">
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
                        <p class="text-[10px] text-slate-400 mt-1.5 text-center font-medium">Format: JPEG, PNG, WEBP (Maksimal 2 MB)</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md">
                            Unggah Foto Baru
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
