@extends('layouts.app', ['headerTitle' => 'Roles & Permission Management'])

@section('content')
<div class="space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <h2 class="text-xl font-black text-emerald-950">Manajemen Role & Hak Akses Permission</h2>
        <p class="text-xs text-slate-600 font-medium mt-1">Konfigurasikan permission internal Gateway untuk setiap role pengguna (student, teacher, dudi, admin)</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($roles as $role)
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black text-emerald-700 uppercase tracking-widest block">ROLE</span>
                        <h3 class="text-lg font-black text-slate-900 uppercase">{{ $role->name }} ({{ $role->slug }})</h3>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        {{ $role->users->count() }} Pengguna
                    </span>
                </div>

                <p class="text-xs text-slate-600 font-medium">{{ $role->description }}</p>

                <form method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" class="space-y-3 pt-2 border-t border-slate-100">
                    @csrf
                    @method('PUT')

                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Internal Permissions</label>

                    <div class="space-y-2">
                        @foreach($permissions as $permission)
                            @php $hasPerm = $role->permissions->contains($permission->id); @endphp
                            <label class="flex items-center space-x-2.5 text-xs text-slate-700 font-medium cursor-pointer p-2 rounded-xl hover:bg-emerald-50/50 border border-transparent hover:border-emerald-200">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $hasPerm ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                <div>
                                    <span class="font-bold text-slate-900">{{ $permission->name }}</span>
                                    <span class="block text-[10px] text-emerald-800 font-mono font-bold">{{ $permission->slug }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                            Simpan Permissions {{ $role->slug }}
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
