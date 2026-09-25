@extends('layouts.auth', ['title' => '419 - Sesi Halaman Kedaluwarsa'])

@section('content')
<div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-lg text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>

    <div>
        <h2 class="text-xl font-black text-slate-900">419 - Sesi Kedaluwarsa</h2>
        <p class="text-xs text-blue-700 font-extrabold tracking-wider uppercase mt-1">Perlindungan Keamanan Sesi</p>
    </div>

    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 text-xs text-blue-900 font-semibold text-center leading-relaxed">
        Sesi halaman atau token keamanan formulir Anda telah berakhir karena tidak ada interaksi dalam waktu cukup lama. Silakan muat ulang halaman.
    </div>

    <div class="pt-2 flex flex-col sm:flex-row gap-3">
        <button onclick="window.location.reload()" type="button" class="inline-block flex-1 py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl transition-all text-xs text-center shadow-md shadow-emerald-700/20">
            Muat Ulang Halaman
        </button>
        <a href="{{ route('login') }}" class="inline-block flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs text-center border border-slate-300">
            Ke Halaman Login
        </a>
    </div>
</div>
@endsection
