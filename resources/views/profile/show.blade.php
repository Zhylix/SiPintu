@extends('layouts.app', ['headerTitle' => 'Profil Pengguna'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- User Card Summary -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center space-x-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-indigo-600/30">
                {{ $user->initials() }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">{{ $user->name }}</h2>
                <div class="flex items-center space-x-3 mt-1">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        Role: {{ $user->user_type }}
                    </span>
                    @if($user->external_id)
                        <span class="text-xs text-slate-400">External ID: <code class="text-indigo-300 font-mono">{{ $user->external_id }}</code></span>
                    @endif
                </div>
            </div>
        </div>

        <div class="text-right text-xs text-slate-400">
            <div>Terdaftar sejak: <span class="text-slate-200 font-medium">{{ $user->created_at?->format('d M Y') }}</span></div>
            <div class="mt-1">Status Akun: <span class="text-emerald-400 font-bold uppercase">{{ $user->status }}</span></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Edit Profile Form -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <h3 class="text-base font-bold text-white mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Informasi Profil</span>
            </h3>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0812xxxxxxxx"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-all">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <h3 class="text-base font-bold text-white mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span>Ganti Kata Sandi</span>
            </h3>

            @if($user->isStudent())
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400">
                    <p class="font-bold text-amber-400 mb-1">Akun Siswa Tersinkronisasi SIJUNA</p>
                    Kata sandi untuk akun Siswa bersumber dari SIJUNA API dan tidak dapat diubah dari Gateway.
                </div>
            @else
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Kata Sandi Baru</label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-all">
                        Perbarui Kata Sandi
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
