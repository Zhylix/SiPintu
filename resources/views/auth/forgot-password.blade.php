@extends('layouts.auth', ['title' => 'Lupa Password'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-8 shadow-2xl shadow-emerald-900/10">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-emerald-950">Reset Kata Sandi</h2>
        <p class="text-xs text-slate-600 font-semibold mt-1">Masukkan email terdaftar Anda untuk pemulihan kata sandi</p>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
            <input type="email" id="email" name="email" required autofocus placeholder="nama@sekolah.id"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 hover:shadow-emerald-600/40 transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2">
            <span>Kirim Link Pemulihan</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </button>
        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center justify-center space-x-1">
                <span>&larr; Kembali ke Halaman Login</span>
            </a>
        </div>
    </form>
</div>
@endsection

