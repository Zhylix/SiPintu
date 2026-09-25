@extends('layouts.auth', ['title' => '503 - Mode Pemeliharaan'])

@section('content')
<div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-lg text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto animate-pulse">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>

    <div>
        <h2 class="text-xl font-black text-slate-900">503 - Sedang Pemeliharaan</h2>
        <p class="text-xs text-emerald-700 font-extrabold tracking-wider uppercase mt-1">Peningkatan Layanan Sistem</p>
    </div>

    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-950 font-semibold text-center leading-relaxed">
        Portal SiPintu SMKN 1 Bangsri sedang menjalani pemeliharaan berkala atau pembaruan performa. Layanan Single Sign-On dan aplikasi integrasi akan segera beroperasi normal.
    </div>

    <div class="pt-2">
        <button onclick="window.location.reload()" type="button" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl transition-all text-xs text-center shadow-md shadow-emerald-700/20">
            Muat Ulang Halaman
        </button>
    </div>
</div>
@endsection
