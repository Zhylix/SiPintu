@extends('layouts.auth', ['title' => '500 - Gangguan Server Internal'])

@section('content')
<div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-lg text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>

    <div>
        <h2 class="text-xl font-black text-slate-900">500 - Gangguan Server</h2>
        <p class="text-xs text-rose-700 font-extrabold tracking-wider uppercase mt-1">Kesalahan Teknis Internal</p>
    </div>

    <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200/80 text-xs text-rose-900 font-semibold text-center leading-relaxed">
        Terjadi kendala teknis internal yang tidak terduga pada server SiPintu. Tim teknis SMKN 1 Bangsri telah mencatat log sistem untuk pemeriksaan lebih lanjut.
    </div>

    <div class="pt-2 flex flex-col sm:flex-row gap-3">
        <button onclick="window.location.reload()" type="button" class="inline-block flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs text-center border border-slate-300">
            Coba Muat Ulang
        </button>
        <a href="{{ url('/') }}" class="inline-block flex-1 py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl transition-all text-xs text-center shadow-md shadow-emerald-700/20">
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection
