@extends('layouts.app', ['headerTitle' => 'Siswa Bimbingan'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-emerald-950">Daftar Siswa Bimbingan PKL</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Monitoring aktivitas dan evaluasi berkala siswa bimbingan Anda</p>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / External ID</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status PKL</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white font-sans">
                    @foreach($students as $s)
                        <tr class="hover:bg-emerald-50/50">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $s->name }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-800">{{ $s->external_id ?? $s->username ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600 font-semibold">{{ $s->email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Aktif PKL
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('teacher.evaluations') }}" class="text-emerald-700 hover:underline font-extrabold text-[11px]">Beri Evaluasi</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
