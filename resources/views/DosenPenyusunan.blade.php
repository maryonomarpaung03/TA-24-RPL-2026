@extends('layouts.app')

@section('title', 'Rencana Proyek Mahasiswa - PjBL')

@section('content')
<div class="w-full space-y-6"
     x-data="{ commentModal: false, commentTask: { id: '', name: '', comments: [] } }">

    @include('partials.flash-messages')

    <a href="{{ route('dosen.proyek-mahasiswa.show', $id) }}" class="text-blue-500 text-xs font-bold hover:underline inline-block">
        &larr; Kembali ke Detail Proyek
    </a>

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
            <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                pemantauan dosen/ <span class="text-blue-500">Project Planning</span>
            </nav>
        </div>
        <span class="text-[10px] font-bold uppercase text-blue-500 bg-blue-50 px-3 py-1.5 rounded-full">
            <i class="fas fa-comment-dots mr-1"></i>Pantau &amp; Komentar
        </span>
    </div>

    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
        @include('partials.filter-bar', [
            'action' => route('dosen.penyusunan', $id),
            'filters' => [
                ['name' => 'status', 'label' => 'Status', 'value' => $filterState['status'], 'options' => $statusOptions],
                ['name' => 'pj', 'label' => 'Penanggung Jawab', 'value' => $filterState['pj'], 'options' => $pjOptions],
                ['name' => 'tenggat', 'label' => 'Tenggat', 'value' => $filterState['tenggat'], 'options' => $tenggatOptions],
            ],
            'summary' => 'Menampilkan '.count($tasks).' dari '.$totalTasks.' tugas.',
        ])

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="p-4 text-center">No</th>
                        <th class="p-4">Judul Tugas</th>
                        <th class="p-4">Deskripsi</th>
                        <th class="p-4 text-center">Mulai</th>
                        <th class="p-4 text-center">Selesai</th>
                        <th class="p-4 text-center">Penanggung Jawab</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-center">Komentar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                    <tr class="border-t hover:bg-gray-50 transition">
                        <td class="p-4 text-center">{{ $task['no'] }}</td>
                        <td class="p-4 font-bold">{{ $task['judul'] }}</td>
                        <td class="p-4 text-gray-500">{{ $task['deskripsi'] }}</td>
                        <td class="p-4 text-center">{{ $task['mulai'] }}</td>
                        <td class="p-4 text-center">{{ $task['selesai'] }}</td>
                        <td class="p-4 text-center font-semibold">{{ $task['pj'] }}</td>
                        <td class="p-4 text-center">
                            @php
                                $tone = $task['status']['tone'];
                                $toneClass = match ($tone) {
                                    'emerald' => 'bg-emerald-100 text-emerald-700',
                                    'red' => 'bg-red-100 text-red-700',
                                    'amber' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <span class="inline-block whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold {{ $toneClass }}">
                                {{ $task['status']['label'] }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <button type="button"
                                    @click="commentTask = { id: {{ $task['id'] }}, name: @js($task['judul']), comments: @js($task['comments'] ?? []) }; commentModal = true"
                                    class="relative text-blue-500 hover:text-blue-700 transition" title="Komentar tugas">
                                <i class="fas fa-comment-dots"></i>
                                @if(count($task['comments']) > 0)
                                <span class="absolute -top-2 -right-2 inline-flex min-w-[16px] h-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[9px] font-bold text-white">{{ count($task['comments']) }}</span>
                                @endif
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-gray-400">Belum ada tugas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL KOMENTAR -->
    @include('partials.task-comment-modal')
</div>
@endsection
