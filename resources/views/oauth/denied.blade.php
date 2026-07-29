@extends('layouts.auth', ['title' => 'Akses Ditolak'])

@section('content')
<div class="bg-slate-900/90 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-8 shadow-2xl text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    </div>

    <div>
        <h2 class="text-xl font-extrabold text-white">Akses Aplikasi Ditolak</h2>
        <p class="text-xs text-rose-300 font-semibold tracking-wider uppercase mt-1">403 Access Forbidden</p>
    </div>

    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-left text-xs space-y-2">
        <div class="flex justify-between">
            <span class="text-slate-400">Pengguna:</span>
            <span class="font-bold text-white">{{ $user->name }} ({{ $user->email }})</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-400">Role Anda:</span>
            <span class="font-bold text-indigo-400 uppercase">{{ $user->role }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-400">Aplikasi Tujuan:</span>
            <span class="font-bold text-amber-400">{{ $application->name }}</span>
        </div>
    </div>

    <p class="text-xs text-slate-400 leading-relaxed">
        Peran (Role) Anda saat ini tidak dikonfigurasikan untuk diizinkan mengakses aplikasi ini di SiPintu. Silakan hubungi Administrator Sekolah jika Anda membutuhkan akses.
    </p>

    <div class="pt-2">
        <a href="{{ route('home') }}" class="inline-block w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition-all text-xs">
            &larr; Kembali ke Dashboard Gateway
        </a>
    </div>
</div>
@endsection