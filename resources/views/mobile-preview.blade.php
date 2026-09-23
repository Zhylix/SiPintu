@extends('layouts.mobile', ['title' => 'SiPintu Mobile UI/UX Design System Showcase'])

@section('content')
<div class="space-y-4 text-center" x-data="{ activeTab: 'apps', searchQuery: '', selectedCategory: 'all' }">

    <!-- 1. Hero Welcome Card (Centered & Non-wrapping Text) -->
    <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm relative overflow-hidden text-center flex flex-col items-center justify-center space-y-3">
        <div class="w-14 h-14 rounded-2xl bg-emerald-700 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-sm">
            {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
        </div>

        <div class="space-y-1 text-center flex flex-col items-center">
            <span class="inline-flex items-center justify-center px-3 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5 shrink-0"></span>
                {{ auth()->user()->getUserTypeName() ?? 'Pengguna' }} Portal
            </span>
            <h2 class="text-xl font-black text-slate-900 tracking-tight whitespace-nowrap">Hai, {{ auth()->user()->name ?? 'Pengguna Gateway' }}</h2>
            <p class="text-xs text-slate-500 font-medium text-center whitespace-nowrap">Selamat datang di SiPintu Mobile Gateway SMKN 1 Bangsri</p>
        </div>

        <!-- Quick Stats Pill Row (Centered) -->
        <div class="grid grid-cols-2 gap-2 mt-2 pt-3 border-t border-slate-100 w-full text-center">
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col items-center justify-center">
                <span class="text-[10px] text-slate-500 font-bold block whitespace-nowrap">Status Account</span>
                <span class="text-xs font-black text-emerald-700 flex items-center justify-center gap-1 mt-0.5 whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Terverifikasi
                </span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col items-center justify-center">
                <span class="text-[10px] text-slate-500 font-bold block whitespace-nowrap">Identitas / Role</span>
                <span class="text-xs font-black text-slate-800 uppercase mt-0.5 block truncate whitespace-nowrap">
                    {{ auth()->user()->role ?? 'Siswa' }}
                </span>
            </div>
        </div>
    </div>

    <!-- 2. Search & Category Filter Bar (Centered & Non-wrapping Text) -->
    <div class="bg-white border border-slate-200 rounded-2xl p-3 shadow-sm space-y-3 text-center">
        <!-- Search Input -->
        <div class="relative w-full">
            <input type="text" x-model="searchQuery" placeholder="Cari aplikasi atau layanan..."
                   class="w-full text-center pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-700 focus:ring-1 focus:ring-emerald-700 transition-all font-semibold whitespace-nowrap">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>

        <!-- Filter Chips (Centered & Non-wrapping Text) -->
        <div class="flex items-center justify-center space-x-2 overflow-x-auto no-scrollbar pb-1 max-w-full">
            <button @click="selectedCategory = 'all'"
                    :class="selectedCategory === 'all' ? 'bg-emerald-700 text-white font-extrabold shadow-sm' : 'bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap border border-slate-200 shrink-0">
                Semua Aplikasi
            </button>
            <button @click="selectedCategory = 'akademik'"
                    :class="selectedCategory === 'akademik' ? 'bg-emerald-700 text-white font-extrabold shadow-sm' : 'bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap border border-slate-200 shrink-0">
                Akademik
            </button>
            <button @click="selectedCategory = 'layanan'"
                    :class="selectedCategory === 'layanan' ? 'bg-emerald-700 text-white font-extrabold shadow-sm' : 'bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap border border-slate-200 shrink-0">
                Layanan Digital
            </button>
            <button @click="selectedCategory = 'pkl'"
                    :class="selectedCategory === 'pkl' ? 'bg-emerald-700 text-white font-extrabold shadow-sm' : 'bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap border border-slate-200 shrink-0">
                Portal PKL & DUDI
            </button>
        </div>
    </div>

    <!-- 3. Mobile Grid Quick Launch Apps (Centered Layout) -->
    <div class="space-y-2 text-center">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-sm font-black text-slate-900 tracking-tight whitespace-nowrap">Aplikasi Terintegrasi</h3>
            <span class="text-[11px] font-bold text-emerald-700 whitespace-nowrap">Flat Design Specs</span>
        </div>

        <div class="grid grid-cols-2 min-[420px]:grid-cols-4 gap-3 text-center">
            <!-- App Card 1 -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col items-center justify-start hover:border-emerald-600 hover:-translate-y-1 transition-all text-center cursor-pointer active:scale-95 group">
                <div class="w-14 h-14 rounded-[22px] bg-gradient-to-br from-emerald-500 via-teal-600 to-emerald-700 text-white flex items-center justify-center shadow-md shadow-emerald-600/30 ring-2 ring-white/40 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <span class="mt-2.5 font-extrabold text-xs text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight break-words text-center">
                    E-Rapor ESKASABA
                </span>
            </div>

            <!-- App Card 2 -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col items-center justify-start hover:border-emerald-600 hover:-translate-y-1 transition-all text-center cursor-pointer active:scale-95 group">
                <div class="w-14 h-14 rounded-[22px] bg-gradient-to-br from-teal-500 via-cyan-600 to-blue-700 text-white flex items-center justify-center shadow-md shadow-teal-600/30 ring-2 ring-white/40 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="mt-2.5 font-extrabold text-xs text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight break-words text-center">
                    Presensi Presisi
                </span>
            </div>

            <!-- App Card 3 -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col items-center justify-start hover:border-emerald-600 hover:-translate-y-1 transition-all text-center cursor-pointer active:scale-95 group">
                <div class="w-14 h-14 rounded-[22px] bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 text-white flex items-center justify-center shadow-md shadow-blue-600/30 ring-2 ring-white/40 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="mt-2.5 font-extrabold text-xs text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight break-words text-center">
                    Portal PKL & DUDI
                </span>
            </div>

            <!-- App Card 4 -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col items-center justify-start hover:border-emerald-600 hover:-translate-y-1 transition-all text-center cursor-pointer active:scale-95 group">
                <div class="w-14 h-14 rounded-[22px] bg-gradient-to-br from-amber-500 via-orange-600 to-rose-600 text-white flex items-center justify-center shadow-md shadow-amber-600/30 ring-2 ring-white/40 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <span class="mt-2.5 font-extrabold text-xs text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight break-words text-center">
                    SIJUNA Central
                </span>
            </div>
        </div>
    </div>

    <!-- 4. Announcement Card (Centered & Non-wrapping Text) -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2 text-center flex flex-col items-center">
        <div class="flex items-center justify-center space-x-2">
            <span class="px-2.5 py-0.5 rounded text-[9px] font-black uppercase bg-emerald-50 border border-emerald-200 text-emerald-800 whitespace-nowrap">
                PENGUMUMAN RESMI
            </span>
            <span class="text-[10px] text-slate-500 font-semibold whitespace-nowrap">Hari ini, 08:30 WIB</span>
        </div>
        <h4 class="font-extrabold text-xs text-slate-900 whitespace-nowrap">Pemberitahuan Sinkronisasi SSO Mobile SiPintu</h4>
        <p class="text-xs text-slate-600 leading-relaxed font-medium text-center">
            Pengguna mobile dapat melakukan Single Sign-On (SSO) ke seluruh aplikasi mitra sekolah menggunakan kredensial akun terpadu ini tanpa perlu login ulang.
        </p>
    </div>

    <!-- 5. Color Tokens Check (Centered) -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3 text-center">
        <h4 class="font-black text-xs text-slate-900 uppercase tracking-wider whitespace-nowrap">Mobile Flat Color Tokens Check</h4>
        
        <div class="grid grid-cols-2 gap-2 text-[11px] font-bold text-center">
            <div class="p-2.5 rounded-xl bg-emerald-700 text-white whitespace-nowrap">
                Primary (#047857)
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-800 text-white whitespace-nowrap">
                Primary Hover (#065F46)
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-900 text-white whitespace-nowrap">
                Primary Active (#064E3B)
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap">
                Primary Soft (#ECFDF5)
            </div>
            <div class="p-2.5 rounded-xl bg-green-500 text-white whitespace-nowrap">
                Success (#22C55E)
            </div>
            <div class="p-2.5 rounded-xl bg-amber-500 text-white whitespace-nowrap">
                Warning (#F59E0B)
            </div>
            <div class="p-2.5 rounded-xl bg-red-500 text-white whitespace-nowrap">
                Error (#EF4444)
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-700 text-white whitespace-nowrap">
                Info (#047857)
            </div>
        </div>
    </div>

</div>
@endsection
