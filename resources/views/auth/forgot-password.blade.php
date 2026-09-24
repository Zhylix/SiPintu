@extends('layouts.auth', ['title' => 'Lupa Kata Sandi'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-900/10" x-data="{ tab: 'whatsapp' }">
    <div class="mb-6 text-center space-y-1">
        <h2 class="text-xl font-black text-emerald-950">Pemulihan Kata Sandi</h2>
        <p class="text-xs text-slate-600 font-medium">Pilih metode pemulihan kata sandi akun Anda</p>
    </div>

    <!-- Tab Selector -->
    <div class="flex items-center p-1 rounded-2xl bg-slate-100 border border-slate-200 mb-6 text-xs font-bold">
        <button type="button" @click="tab = 'whatsapp'"
            :class="tab === 'whatsapp' ? 'bg-white text-emerald-900 shadow-xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900'"
            class="flex-1 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <span>WhatsApp OTP (Instan)</span>
        </button>
        <button type="button" @click="tab = 'email'"
            :class="tab === 'email' ? 'bg-white text-emerald-900 shadow-xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900'"
            class="flex-1 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Link Email</span>
        </button>
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

    <!-- TAB 1: WhatsApp OTP -->
    <div x-show="tab === 'whatsapp'" x-transition:enter="transition ease-out duration-200">
        <form method="POST" action="{{ route('password.whatsapp.otp') }}" class="space-y-4">
            @csrf
            <div>
                <label for="identity_wa" class="block text-xs font-extrabold text-slate-700 mb-1.5">NIS / NIP / Email / Username <span class="text-rose-500">*</span></label>
                <input type="text" id="identity_wa" name="identity" required autofocus placeholder="Contoh: 4439 atau 1985..." value="{{ old('identity') }}"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all text-sm font-semibold shadow-2xs">
                <p class="text-[11px] text-slate-500 font-medium mt-1.5">Kode OTP 6 digit akan dikirimkan langsung ke nomor WhatsApp yang terdaftar pada akun tersebut.</p>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-700/25 hover:shadow-emerald-700/35 transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2 cursor-pointer">
                <span>Kirim Kode OTP via WhatsApp</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <!-- TAB 2: Email Reset Link -->
    <div x-show="tab === 'email'" x-transition:enter="transition ease-out duration-200" x-cloak>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-extrabold text-slate-700 mb-1.5">Alamat Email Terdaftar <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" required placeholder="nama@smkn1bangsri.sch.id" value="{{ old('email') }}"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all text-sm font-semibold shadow-2xs">
                <p class="text-[11px] text-slate-500 font-medium mt-1.5">Tautan khusus untuk menyetel ulang kata sandi akan dikirim ke alamat email ini.</p>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-900 text-white font-extrabold rounded-xl shadow-md transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2 cursor-pointer">
                <span>Kirim Link ke Email</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <div class="text-center pt-5 border-t border-slate-100 mt-5">
        <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center justify-center space-x-1">
            <span>&larr; Kembali ke Halaman Login</span>
        </a>
    </div>
</div>
@endsection
