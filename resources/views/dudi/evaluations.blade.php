@extends('layouts.app', ['headerTitle' => 'Evaluasi & Penilaian Industri'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
        <h3 class="text-base font-black text-emerald-950">Evaluasi & Feedback Kinerja Peserta Magang</h3>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">Divisi / Posisi</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Nilai Industri</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white font-sans">
                    @foreach($evaluations as $ev)
                        <tr class="hover:bg-emerald-50/50">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $ev['student_name'] }}</td>
                            <td class="px-4 py-3 text-slate-600 font-semibold">{{ $ev['division'] }}</td>
                            <td class="px-4 py-3 text-emerald-800 font-mono font-bold">{{ $ev['period'] }}</td>
                            <td class="px-4 py-3 font-bold font-mono text-emerald-700 text-sm">{{ $ev['score'] ?? 'Belum Dinilai' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
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
