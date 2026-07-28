@extends('layouts.auth', ['title' => 'SSO Error - Gateway'])

@section('content')
<div class="bg-slate-900/90 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-8 shadow-2xl text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>

    <div>
        <h2 class="text-xl font-extrabold text-white">{{ $title ?? 'OAuth SSO Error' }}</h2>
        <p class="text-xs text-rose-300 font-semibold tracking-wider uppercase mt-1">Single Sign-On Validation Failed</p>
    </div>

    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs text-slate-300 text-left">
        {{ $message ?? 'Terjadi kesalahan saat memproses otentikasi OAuth 2.0.' }}
    </div>

    <div class="pt-2">
        <a href="{{ route('home') }}" class="inline-block w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition-all text-xs">
            &larr; Kembali ke Gateway
        </a>
    </div>
</div>
@endsection
