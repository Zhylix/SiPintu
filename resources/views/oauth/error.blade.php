@extends('layouts.auth', ['title' => 'SSO Error - Gateway'])

@section('content')
<div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-lg text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </div>

    <div>
        <h2 class="text-xl font-black text-slate-900">{{ $title ?? 'OAuth SSO Error' }}</h2>
        <p class="text-xs text-rose-700 font-extrabold tracking-wider uppercase mt-1">Validation Failed</p>
    </div>

    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 font-medium text-left">
        {{ $message ?? 'Terjadi kesalahan saat memproses otentikasi OAuth 2.0.' }}
    </div>

    <div class="pt-2">
        <a href="{{ route('home') }}" class="inline-block w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl transition-all text-xs shadow-md shadow-emerald-700/20">
            &larr; Kembali ke Gateway
        </a>
    </div>
</div>
@endsection
