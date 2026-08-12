@extends('layouts.app', ['headerTitle' => 'Manajemen Role & Hak Akses'])

@section('content')
<div class="space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <h2 class="text-xl font-black text-emerald-950">Manajemen Role & Hak Akses (Permissions)</h2>
        <p class="text-xs text-slate-600 font-medium mt-1">Konfigurasikan hak akses internal Gateway untuk setiap peran pengguna (Siswa, Alumni, Guru, Mitra DUDI, Administrator)</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($roles as $role)
            @php $isAdminRole = in_array(strtolower($role->name), ['admin', 'administrator']); @endphp
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest block">PERAN / ROLE</span>
                        <h3 class="text-lg font-black text-slate-900 uppercase">{{ $role->getDisplayName() }}</h3>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($isAdminRole)
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-300">
                                Terkunci
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                {{ $role->users->count() }} Pengguna
                            </span>
                        @endif
                    </div>
                </div>

                <p class="text-xs text-slate-600 font-medium">{{ $role->getDescription() }}</p>

                @if($isAdminRole)
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 font-semibold space-y-2">
                        <div class="flex items-center space-x-2 font-bold text-amber-950">
                            <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m0-6h4m-2 0V7m0 0a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                            <span>Hak Akses Administrator Terkunci</span>
                        </div>
                        <p class="text-[11px] text-amber-800 font-medium">Role Administrator memiliki hak akses penuh (Full Control) ke seluruh fitur Gateway. Demi keamanan sistem, hak akses ini tidak dapat diubah.</p>
                    </div>

                    <div class="space-y-2 opacity-60">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Hak Akses Internal (Semua Aktif)</label>
                        @foreach($permissions as $permission)
                            <label class="flex items-center space-x-2.5 text-xs text-slate-500 font-medium cursor-not-allowed p-2 rounded-xl border border-slate-100 bg-slate-50">
                                <input type="checkbox" checked disabled class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 cursor-not-allowed">
                                <div>
                                    <span class="font-bold text-slate-800">{{ $permission->getDisplayName() }}</span>
                                    <span class="block text-[10px] text-slate-500 font-mono font-bold">{{ $permission->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" class="space-y-3 pt-2 border-t border-slate-100">
                        @csrf
                        @method('PUT')

                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Hak Akses Internal (Permissions)</label>

                        <div class="space-y-2">
                            @foreach($permissions as $permission)
                                @php $hasPerm = $role->permissions->contains($permission->id); @endphp
                                <label class="flex items-center space-x-2.5 text-xs text-slate-700 font-medium cursor-pointer p-2 rounded-xl hover:bg-emerald-50/50 border border-transparent hover:border-emerald-200">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $hasPerm ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                    <div>
                                        <span class="font-bold text-slate-900">{{ $permission->getDisplayName() }}</span>
                                        <span class="block text-[10px] text-emerald-800 font-mono font-bold">{{ $permission->name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="pt-3">
                            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                                Simpan Hak Akses {{ $role->getDisplayName() }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
