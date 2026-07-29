@extends('layouts.auth', ['title' => 'Login'])

@section('content')
<div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-slate-950/80">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-white">Masuk ke Akun Gateway</h2>
        <p class="text-sm text-slate-400 mt-1">Satu akun untuk mengakses seluruh layanan aplikasi eksternal</p>
    </div>

    @if(session('info'))
        <div class="mb-6 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ accountType: '{{ old('account_type', 'siswa') }}', identity: '{{ old('identity') }}', password: '' }" class="space-y-5">
        @csrf
        <input type="hidden" name="account_type" :value="accountType">

        <!-- Account Type Helper Tabs -->
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Portal Login</label>
            <div class="grid grid-cols-3 gap-2 p-1 bg-slate-950/60 rounded-xl border border-slate-800 text-xs">
                <button type="button" @click="accountType = 'siswa'" :class="accountType === 'siswa' ? 'bg-indigo-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition-all text-center">
                    Siswa
                </button>
                <button type="button" @click="accountType = 'guru'" :class="accountType === 'guru' ? 'bg-indigo-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition-all text-center">
                    Guru
                </button>
                <button type="button" @click="accountType = 'dudi'" :class="accountType === 'dudi' ? 'bg-indigo-600 text-white font-semibold shadow' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition-all text-center">
                    Mitra DUDI
                </button>
            </div>
        </div>

        <!-- Identity Input -->
        <div>
            <label for="identity" class="block text-xs font-semibold text-slate-300 mb-1.5">
                <span x-show="accountType === 'siswa'">Identifier Siswa / NISN / Email SIJUNA</span>
                <span x-show="accountType === 'guru'">Email atau Username Guru</span>
                <span x-show="accountType === 'dudi'">Email atau Username Mitra DUDI</span>
            </label>
            <input type="text" id="identity" name="identity" required autofocus x-model="identity"
                :placeholder="accountType === 'siswa' ? 'Contoh: SIJ-STUDENT-001 atau NISN' : (accountType === 'guru' ? 'Contoh: guru@sekolah.id' : 'Contoh: admin@majujaya.co.id')"
                class="w-full px-4 py-3 rounded-xl bg-slate-950/80 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
        </div>

        <!-- Password Input (hidden for student SIJUNA login) -->
        <div x-show="accountType !== 'siswa'">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-300">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="text-xs text-indigo-400 hover:underline">Lupa Password?</a>
            </div>
            <input type="password" id="password" name="password" x-model="password"
                placeholder="••••••••"
                class="w-full px-4 py-3 rounded-xl bg-slate-950/80 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
        </div>

        <div class="flex items-center justify-between text-xs text-slate-400">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                <span>Ingat Saya</span>
            </label>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 hover:shadow-indigo-500/50 transition-all transform active:scale-[0.99] text-sm">
            Masuk Sekarang &rarr;
        </button>
    </form>
</div>
@endsection
