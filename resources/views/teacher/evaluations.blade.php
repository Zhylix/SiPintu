@extends('layouts.app', ['headerTitle' => 'Penilaian & Evaluasi'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h3 class="text-base font-bold text-white">Penilaian Akhir & Evaluasi Siswa</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN</th>
                        <th class="px-4 py-3">Mitra DUDI</th>
                        <th class="px-4 py-3">Nilai</th>
                        <th class="px-4 py-3">Status Penilaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($evaluations as $ev)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-semibold text-white">{{ $ev['student_name'] }}</td>
                            <td class="px-4 py-3 font-mono text-purple-300">{{ $ev['nisn'] }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $ev['dudi'] }}</td>
                            <td class="px-4 py-3 font-bold font-mono text-amber-400">{{ $ev['score'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    {{ $ev['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
