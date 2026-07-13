@extends('layouts.app')

@section('title', 'Penilaian Dosen - PjBL')

@section('content')
<div class="w-full space-y-6">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
            <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                <a href="{{ route('my-project') }}" class="hover:text-blue-500">projek saya</a> /
                <span class="text-gray-400">Pelaksanaan dan evaluasi</span>
            </nav>
        </div>
    </div>

    <div class="flex space-x-12">
        <div class="w-24 space-y-4">
            @php
                $menus = [
                    ['icon' => 'fa-comments', 'label' => 'Dekomposisi Masalah', 'r' => 'dekomposisi'],
                    ['icon' => 'fa-tasks', 'label' => 'Penyusunan Rencana', 'r' => 'penyusunan'],
                    ['icon' => 'fa-calendar-alt', 'label' => 'Waktu Progres', 'r' => 'waktu-progres'],
                    ['icon' => 'fa-project-diagram', 'label' => 'Pelaksanaan & Evaluasi', 'r' => 'pelaksanaan'],
                    ['icon' => 'fa-clipboard-check', 'label' => 'Penilaian', 'r' => '#'],
                ];
            @endphp
            @foreach($menus as $index => $m)
                <div class="relative group">
                    <a href="{{ $m['r'] != '#' ? route($m['r'], $id) : '#' }}"
                       class="{{ $index == 4 ? 'bg-blue-200' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                        <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                    </a>
                    <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                        {{ $m['label'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex-1 space-y-6">
            <div class="flex space-x-8 border-b border-gray-200 text-sm font-bold">
                <a href="{{ route('penilaian-kelompok', $id) }}" class="text-gray-400 pb-2 hover:text-blue-600 transition">Penilaian Kelompok</a>
                <span class="text-blue-600 border-b-2 border-blue-600 pb-2">Penilaian Dosen</span>
            </div>

            {{-- Ringkasan --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-slate-500 uppercase">Nilai Kelompok</p>
                    <p class="mt-2 text-4xl font-black text-blue-600">{{ $penilaian['group_score'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Grade {{ $penilaian['group_grade'] }}</p>
                </div>
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-slate-500 uppercase">Nilai Anda</p>
                    <p class="mt-2 text-4xl font-black text-slate-900">{{ $penilaian['own_score'] ?? '-' }}</p>
                    <p class="text-xs text-slate-500 mt-1">Grade {{ $penilaian['own_grade'] }}</p>
                </div>
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-slate-500 uppercase">Status</p>
                    <p class="mt-2 text-lg font-bold text-emerald-600">{{ $penilaian['group_status'] }}</p>
                </div>
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-slate-500 uppercase">Dinilai Oleh</p>
                    <p class="mt-2 text-sm font-bold text-slate-900">{{ $penilaian['evaluator'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $penilaian['evaluated_at'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                {{-- Komponen kelompok --}}
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Komponen Penilaian Kelompok</h3>
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Komponen</th>
                                    <th class="px-4 py-3 text-left font-semibold">Bobot</th>
                                    <th class="px-4 py-3 text-right font-semibold">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penilaian['components'] as $component)
                                <tr class="border-t border-slate-200">
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $component['component'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $component['weight'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ $component['score'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($penilaian['criteria'])
                    <h3 class="text-lg font-bold text-slate-900 mt-8 mb-4">Kriteria Penilaian Anda</h3>
                    <div class="space-y-4">
                        @foreach($penilaian['criteria'] as $item)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-slate-800">{{ $item['criterion'] }}</p>
                                <p class="text-sm font-bold text-slate-900">{{ $item['score'] }}/100 &middot; {{ $item['grade'] }}</p>
                            </div>
                            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: {{ $item['score'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Catatan --}}
                <aside class="space-y-6">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                        <h4 class="text-sm font-bold text-slate-900 mb-3">Catatan untuk Kelompok</h4>
                        <p class="text-sm leading-relaxed text-slate-600 italic">
                            "{{ $penilaian['note'] ?: 'Tidak ada catatan.' }}"
                        </p>
                    </div>

                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                        <h4 class="text-sm font-bold text-slate-900 mb-3">Umpan Balik untuk Anda</h4>
                        <p class="text-sm leading-relaxed text-slate-600 italic">
                            "{{ $penilaian['own_feedback'] ?: 'Tidak ada umpan balik khusus.' }}"
                        </p>
                    </div>

                    <p class="text-[10px] text-red-400 font-bold italic px-2">*Hanya dapat dilihat. Penilaian bersifat mutlak.</p>
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection
