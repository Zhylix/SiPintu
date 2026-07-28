@extends('layouts.app', ['headerTitle' => 'Roles & Permission Management'])

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-white">Manajemen Role & Hak Akses Permission</h2>
        <p class="text-xs text-slate-400 mt-1">Konfigurasikan permission internal Gateway untuk setiap role pengguna (student, teacher, dudi, admin)</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($roles as $role)
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest block">ROLE</span>
                        <h3 class="text-lg font-bold text-white uppercase">{{ $role->name }} ({{ $role->slug }})</h3>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300">
                        {{ $role->users->count() }} Pengguna
                    </span>
                </div>

                <p class="text-xs text-slate-400">{{ $role->description }}</p>

                <form method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" class="space-y-3 pt-2 border-t border-slate-800">
                    @csrf
                    @method('PUT')

                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Internal Permissions</label>

                    <div class="space-y-2">
                        @foreach($permissions as $permission)
                            @php $hasPerm = $role->permissions->contains($permission->id); @endphp
                            <label class="flex items-center space-x-2.5 text-xs text-slate-300 cursor-pointer p-2 rounded-lg hover:bg-slate-950/60">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $hasPerm ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="font-bold text-white">{{ $permission->name }}</span>
                                    <span class="block text-[10px] text-slate-400 font-mono">{{ $permission->slug }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl transition-all">
                            Simpan Permissions {{ $role->slug }}
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
