@extends('layouts.auth', ['title' => 'Verifikasi OTP WhatsApp'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-900/10"
     x-data="{ showNew: false, showConfirm: false }">
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
                value="{{ old('otp') }}"
                class="w-full text-center tracking-[0.5em] px-4 py-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-mono text-xl font-black placeholder:tracking-normal placeholder:font-sans placeholder:text-sm placeholder:font-normal focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
        </div>

        <div>
            <label for="password" class="block text-xs font-extrabold text-slate-700 mb-1.5 uppercase tracking-wide">Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <div class="relative">
                <input type="password" :type="showNew ? 'text' : 'password'" id="password" name="password" required minlength="8" autocomplete="new-password"
                    placeholder="Minimal 8 karakter baru"
                    class="w-full pl-4 pr-11 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                <button type="button" @click="showNew = !showNew"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-extrabold text-slate-700 mb-1.5 uppercase tracking-wide">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <div class="relative">
                <input type="password" :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"
                    placeholder="Ketik ulang kata sandi baru"
                    class="w-full pl-4 pr-11 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                <button type="button" @click="showConfirm = !showConfirm"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
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
