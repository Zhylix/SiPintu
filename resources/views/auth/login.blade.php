@extends('layouts.auth', ['title' => 'Login Gateway'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-8 shadow-2xl shadow-emerald-900/10">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-emerald-950">Portal Login Gateway Sekolah</h2>
        <p class="text-xs text-slate-600 font-semibold mt-1">Layanan Akses Terpadu Terintegrasi SMKN 1 Bangsri</p>
    </div>

    @if(session('info'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
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
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pilih Peran Akun Gateway</label>
            <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 rounded-xl border border-slate-200 text-xs">
                <button type="button" @click="accountType = 'siswa'" :class="accountType === 'siswa' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    Siswa
                </button>
                <button type="button" @click="accountType = 'guru'" :class="accountType === 'guru' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    Guru
                </button>
                <button type="button" @click="accountType = 'dudi'" :class="accountType === 'dudi' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    Mitra DUDI
                </button>
            </div>
        </div>

        <!-- Identity Input -->
        <div>
            <label for="identity" class="block text-xs font-bold text-slate-700 mb-1.5">
                <span x-show="accountType === 'siswa'">Identifier Siswa / NISN / Email SIJUNA</span>
                <span x-show="accountType === 'guru'">Email atau Username Guru</span>
                <span x-show="accountType === 'dudi'">Email atau Username Mitra DUDI</span>
            </label>
            <input type="text" id="identity" name="identity" required autofocus x-model="identity"
                :placeholder="accountType === 'siswa' ? 'Contoh: NIS Siswa' : (accountType === 'guru' ? 'Contoh: guru@smkn1bangsri.sch.id' : 'Contoh: admin@majujaya.co.id')"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
        </div>

        <!-- Password Input -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-bold text-slate-700">
                    Kata Sandi <span x-show="accountType === 'siswa'" class="text-slate-500 font-normal text-[11px]">(Guru / DUDI / Admin)</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline">Lupa Password?</a>
            </div>
            <input type="password" id="password" name="password" x-model="password"
                placeholder="••••••••"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
        </div>

        <div class="flex items-center justify-between text-xs text-slate-600">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded bg-slate-100 border-slate-300 text-emerald-600 focus:ring-emerald-600">
                <span class="font-semibold text-slate-700">Ingat Sesi Saya</span>
            </label>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 hover:shadow-emerald-600/40 transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2">
            <span>Masuk ke Gateway SMKN 1 Bangsri</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </button>
    </form>
</div>
@endsection
