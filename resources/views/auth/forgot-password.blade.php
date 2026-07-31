@extends('layouts.auth', ['title' => 'Lupa Password'])

@section('content')
<div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-slate-950/80">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-white">Reset Kata Sandi</h2>
        <p class="text-sm text-slate-400 mt-1">Masukkan email terdaftar Anda untuk pemulihan kata sandi</p>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
            <input type="email" id="email" name="email" required autofocus placeholder="nama@sekolah.id"
                class="w-full px-4 py-3 rounded-xl bg-slate-950/80 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all text-sm">
            Kirim Link Pemulihan &rarr;
        </button>
        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">&larr; Kembali ke Halaman Login</a>
        </div>
    </form>
</div>
@endsection
