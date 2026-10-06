@extends('layouts.auth', ['title' => '500 - Gangguan Server Internal'])

@section('content')
@php
    $incidentCode = $incidentCode ?? request()->attributes->get('incident_code');
    $errorLog = request()->attributes->get('current_error_log');
@endphp
<div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl text-center space-y-5" x-data="{ copied: false }">
    <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto shadow-inner">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>

    <div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">500 - Gangguan Server</h2>
        <p class="text-xs text-rose-700 font-extrabold tracking-wider uppercase mt-1">Kesalahan Teknis Internal Terdeteksi</p>
    </div>

    @if($incidentCode)
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 flex flex-col sm:flex-row items-center justify-between gap-2.5">
        <div class="text-left">
            <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 block">Kode Referensi Insiden</span>
            <span class="font-mono text-xs font-black text-slate-800 tracking-wide select-all">{{ $incidentCode }}</span>
        </div>
        <button type="button" 
                @click="navigator.clipboard.writeText('{{ $incidentCode }}'); copied = true; setTimeout(() => copied = false, 2500)"
                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 shrink-0">
            <template x-if="!copied">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Salin Kode
                </span>
            </template>
            <template x-if="copied">
                <span class="flex items-center gap-1 text-emerald-700 font-bold">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Tersalin!
                </span>
            </template>
        </button>
    </div>
    @endif

    <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200/80 text-xs text-rose-950 font-medium text-center leading-relaxed">
        Terjadi kendala teknis internal yang tidak terduga pada server SiPintu. 
        <strong class="font-bold text-rose-900 block mt-1">Laporan insiden telah otomatis dicatat dan diteruskan ke Tim Administrator IT SMKN 1 Bangsri untuk penanganan lebih lanjut.</strong>
    </div>

    @if(auth()->check() && auth()->user()->isAdmin())
    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-left flex items-center justify-between gap-3">
        <div class="text-xs">
            <span class="font-extrabold text-emerald-950 block">Akses Administrator</span>
            <span class="text-[11px] text-emerald-800">Anda dapat meninjau log lengkap kendala ini di panel admin.</span>
        </div>
        <a href="{{ $errorLog ? route('admin.error-logs.show', $errorLog->id) : route('admin.error-logs.index') }}" 
           class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold whitespace-nowrap shadow-xs transition-colors">
            Buka Log Error &rarr;
        </a>
    </div>
    @endif

    <div class="pt-2 flex flex-col sm:flex-row gap-2.5">
        <button onclick="window.location.reload()" type="button" class="inline-flex items-center justify-center flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-xl transition-all text-xs text-center border border-slate-300">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Muat Ulang
        </button>
    </div>
</div>
@endsection
