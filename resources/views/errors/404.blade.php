@extends('layouts.auth', ['title' => '404 - Halaman Tidak Ditemukan'])

@section('content')
<div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-lg text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>

    <div>
        <h2 class="text-xl font-black text-slate-900">404 - Halaman Tidak Ditemukan</h2>
        <p class="text-xs text-amber-700 font-extrabold tracking-wider uppercase mt-1">Alamat Tidak Tersedia</p>
    </div>

    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-900 font-semibold text-center leading-relaxed">
        Halaman atau rute yang Anda tuju tidak tersedia, telah dipindahkan, atau tautan yang Anda klik keliru.
    </div>

    <div class="pt-2 flex flex-col sm:flex-row gap-3">
        <a href="javascript:history.back()" class="inline-block flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs text-center border border-slate-300">
            &larr; Kembali
        </a>
        <a href="{{ url('/') }}" class="inline-block flex-1 py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl transition-all text-xs text-center shadow-md shadow-emerald-700/20">
            Kembali ke Beranda &rarr;
        </a>
    </div>
</div>
@endsection
