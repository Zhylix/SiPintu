@extends('layouts.auth', ['title' => 'Login'])

@section('content')
<div class="bg-white border border-emerald-100 rounded-3xl p-8 shadow-2xl shadow-emerald-900/10">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-emerald-950">Portal Login Gateway Sekolah</h2>
        <p class="text-xs text-slate-600 font-semibold mt-1">Layanan Akses SMKN 1 Bangsri</p>
    </div>

    @if(session('info'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1.5">
            @foreach(array_unique($errors->all()) as $error)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-medium">{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ accountType: '{{ old('account_type', 'siswa') }}', password: '', showPassword: false }" class="space-y-5">
        @csrf
        <input type="hidden" name="account_type" :value="accountType">

        <!-- Account Type Helper Tabs -->
        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pilih Tipe Akun</label>
            <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 rounded-xl border border-slate-200 text-xs">
                <button type="button" @click="accountType = 'siswa'" :class="accountType === 'siswa' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    Siswa
                </button>
                <button type="button" @click="accountType = 'guru'" :class="accountType === 'guru' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    Guru
                </button>
                <button type="button" @click="accountType = 'dudi'" :class="accountType === 'dudi' ? 'bg-emerald-600 text-white font-extrabold shadow-md shadow-emerald-600/30' : 'text-slate-600 hover:text-emerald-700 hover:bg-white'" class="py-2.5 rounded-lg transition-all text-center">
                    DUDI
                </button>
            </div>
        </div>

        <!-- Group 1: Siswa Field (NIS) -->
        <div x-show="accountType === 'siswa'">
            <label for="nis" class="block text-xs font-bold text-slate-700 mb-1.5">
                Nomor Induk Siswa (NIS)
            </label>
            <input type="text" id="nis" name="nis" :required="accountType === 'siswa'" :disabled="accountType !== 'siswa'"
                value="{{ old('nis', old('identity')) }}" placeholder="Contoh NIS: 4439"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
            <p class="text-[11px] text-slate-600 mt-1 font-medium">Gunakan Nomor Induk Siswa (NIS) aktif terdaftar di SIJUNA.</p>
        </div>

        <!-- Group 2: Guru Field (NIP) -->
        <div x-show="accountType === 'guru'">
            <label for="nip" class="block text-xs font-bold text-slate-700 mb-1.5">
                Nomor Induk Pegawai (NIP) / Email Guru
            </label>
            <input type="text" id="nip" name="nip" :required="accountType === 'guru'" :disabled="accountType !== 'guru'"
                value="{{ old('nip', old('identity')) }}" placeholder="NIP atau Email"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
            <p class="text-[11px] text-slate-600 mt-1 font-medium">Gunakan NIP resmi atau email terdaftar pendidik di SIJUNA.</p>
        </div>

        <!-- Group 3: DUDI Field (Kode DUDI) -->
        <div x-show="accountType === 'dudi'">
            <label for="kode_dudi" class="block text-xs font-bold text-slate-700 mb-1.5">
                Kode Mitra DUDI / Email Perusahaan
            </label>
            <input type="text" id="kode_dudi" name="kode_dudi" :required="accountType === 'dudi'" :disabled="accountType !== 'dudi'"
                value="{{ old('kode_dudi', old('identity')) }}" placeholder="Contoh Kode: dudi atau Email"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
            <p class="text-[11px] text-slate-600 mt-1 font-medium">Gunakan Kode Mitra DUDI atau Email resmi instansi/perusahaan.</p>
        </div>

        <!-- Password Input with Toggle -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-bold text-slate-700">
                    Kata Sandi
                </label>
                <a href="{{ route('password.request') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline">Lupa Password?</a>
            </div>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required x-model="password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all text-sm font-semibold">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 013.98-1.063c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21f-9.542-7-9.542-7M3 3l18 18"></path></svg>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-600">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded bg-slate-100 border-slate-300 text-emerald-600 focus:ring-emerald-600">
                <span class="font-semibold text-slate-700">Ingat Sesi Saya</span>
            </label>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 hover:shadow-emerald-600/40 transition-all transform active:scale-[0.99] text-sm flex items-center justify-center space-x-2">
            <span>Login ke Dashboard</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </button>

        <div class="mt-4 pt-3 border-t border-slate-100 text-center">
            <p class="text-[11px] text-slate-500 font-medium">
                <span class="font-bold text-slate-700">Catatan Admin:</span> Administrator dapat login melalui tab mana saja dengan Email/Username Admin.
            </p>
        </div>
    </form>
</div>
@endsection
