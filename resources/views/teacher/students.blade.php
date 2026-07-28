@extends('layouts.app', ['headerTitle' => 'Data Siswa Bimbingan Guru'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Monitoring & Bimbingan Siswa</h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar siswa yang berada di bawah bimbingan PKL Guru</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / External ID</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($students as $student)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-semibold text-white">{{ $student->name }}</td>
                            <td class="px-4 py-3 font-mono text-purple-300">{{ $student->external_id ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $student->email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ $student->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4">
            {{ $students->links() }}
        </div>
    </div>
</div>
@endsection
