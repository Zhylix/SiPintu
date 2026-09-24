@extends('layouts.auth', ['title' => 'Verifikasi OTP WhatsApp'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-900/10">
    <div class="mb-6 text-center space-y-2">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center mx-auto shadow-sm">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h2 class="text-xl font-black text-emerald-950">Verifikasi OTP WhatsApp</h2>
        <p class="text-xs text-slate-600 font-medium">
            Masukkan kode 6 digit yang dikirimkan ke <span class="font-bold text-emerald-800 font-mono">{{ $maskedPhone }}</span>
        </p>
    </div>

    @if(session('status'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.whatsapp.verify') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">

        <div>
            <label for="otp" class="block text-xs font-extrabold text-slate-700 mb-1.5 uppercase tracking-wide">Kode OTP 6 Digit <span class="text-rose-500">*</span></label>
            <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus placeholder="Contoh: 123456"
                class="w-full text-center tracking-[0.5em] px-4 py-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-mono text-xl font-black placeholder:tracking-normal placeholder:font-sans placeholder:text-sm placeholder:font-normal focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
        </div>

        <div>
            <label for="password" class="block text-xs font-extrabold text-slate-700 mb-1.5">Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-extrabold text-slate-700 mb-1.5">Ulangi Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Ketik ulang kata sandi baru"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-700/25 hover:shadow-emerald-700/35 transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2 cursor-pointer mt-2">
            <span>Perbarui Kata Sandi</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        <div class="text-center pt-3 flex flex-col space-y-2">
            <a href="{{ route('password.request') }}" class="text-xs font-bold text-slate-600 hover:text-emerald-700 transition-colors">
                Kirim ulang OTP atau ganti nomor
            </a>
            <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline">
                &larr; Kembali ke Halaman Login
            </a>
        </div>
    </form>
</div>
@endsection
