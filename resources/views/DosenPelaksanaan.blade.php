@extends('layouts.app')

@section('title', 'Pelaksanaan Proyek Mahasiswa - DELPRO')

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
                pemantauan dosen/ <span class="text-blue-500">Execution &amp; Evaluation</span>
            </nav>
        </div>
        <span class="text-[10px] font-bold uppercase text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full">
            <i class="fas fa-eye mr-1"></i>Hanya lihat
        </span>
    </div>

    <!-- RINGKASAN PROGRES -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Total Tugas</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $progress['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Selesai</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $progress['done'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Progres</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $progress['percent'] }}%</p>
            <div class="mt-2 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $progress['percent'] }}%"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Terlambat</p>
            <p class="text-3xl font-bold {{ $progress['overdue'] > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $progress['overdue'] }}</p>
        </div>
    </div>

    <!-- PERSETUJUAN TUGAS -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8"
         x-data="{ rejectId: null, rejectName: '' }">
        <div class="flex items-center gap-2 mb-1">
            <h3 class="text-sm font-bold text-gray-800 uppercase">Persetujuan Tugas</h3>
            @if(count($pendingApprovals) > 0)
            <span class="text-[10px] font-bold text-white bg-purple-600 rounded-full px-2 py-0.5">{{ count($pendingApprovals) }}</span>
            @endif
        </div>
        <p class="text-xs text-gray-500 mb-5">Tugas yang menunggu persetujuan Anda untuk berpindah kolom.</p>

        @forelse($pendingApprovals as $ap)
        <div class="rounded-2xl border border-gray-100 p-4 mb-3 hover:border-purple-200 transition">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-sm">{{ $ap['task_title'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <span class="font-semibold">{{ $ap['requester'] }}</span> mengajukan
                        &middot; {{ $ap['from_label'] }} <i class="fas fa-arrow-right text-[9px] mx-1"></i> <span class="font-semibold text-purple-700">{{ $ap['to_label'] }}</span>
                        &middot; {{ $ap['requested_at'] }}
                    </p>
                    @if(!empty($ap['checklist']))
                    <div class="mt-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Checklist dipenuhi</p>
                        <ul class="space-y-0.5">
                            @foreach($ap['checklist'] as $item)
                            <li class="text-xs text-gray-600"><i class="fas fa-check-circle text-green-500 mr-1"></i>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form method="POST" action="{{ route('dosen.pelaksanaan.approve', [$id, $ap['id']]) }}">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-green-700 transition">
                            <i class="fas fa-check mr-1"></i>Setujui
                        </button>
                    </form>
                    <button type="button"
                            @click="rejectId = {{ $ap['id'] }}; rejectName = @js($ap['task_title'])"
                            class="bg-red-50 text-red-600 text-xs font-bold px-4 py-2 rounded-xl hover:bg-red-100 transition">
                        <i class="fas fa-times mr-1"></i>Tolak
                    </button>
                </div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 italic py-6 text-center">Tidak ada tugas yang menunggu persetujuan.</p>
        @endforelse

        <!-- Modal Tolak -->
        <div x-show="rejectId !== null" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl" @click.outside="rejectId = null">
                <h3 class="text-lg font-bold mb-1">Tolak Perpindahan</h3>
                <p class="text-xs text-gray-500 mb-4" x-text="rejectName"></p>
                <form :action="`{{ url('dosen/proyek/'.$id.'/pelaksanaan/approval') }}/${rejectId}/reject`" method="POST">
                    @csrf
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan untuk mahasiswa <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="note" rows="3" maxlength="1000" placeholder="Alasan penolakan / yang perlu diperbaiki..."
                              class="w-full border rounded-xl p-3 text-sm outline-none focus:border-red-400 resize-none mb-5"></textarea>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="rejectId = null" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-bold">Tolak Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KANBAN (pantau + komentar) -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-800 uppercase">Papan Kanban</h3>
            <span class="text-[10px] text-gray-400"><i class="fas fa-comment-dots mr-1"></i>Buka bukti pengumpulan, beri komentar, lalu tandai sudah direview</span>
        </div>

        @include('partials.filter-bar', [
            'action' => route('dosen.pelaksanaan', $id),
            'search' => [
                'name' => 'q',
                'value' => $filterState['q'],
                'placeholder' => 'Cari judul atau deskripsi tugas',
            ],
            'filters' => [
                ['name' => 'pj', 'label' => 'Penanggung Jawab', 'value' => $filterState['pj'], 'options' => $pjOptions],
                ['name' => 'prioritas', 'label' => 'Prioritas', 'value' => $filterState['prioritas'], 'options' => $prioritasOptions],
                ['name' => 'tenggat', 'label' => 'Tenggat', 'value' => $filterState['tenggat'], 'options' => $tenggatOptions],
            ],
            'summary' => 'Menampilkan '.$shownTasks.' dari '.$totalTasks.' tugas di papan.',
        ])

        @include('partials.kanban-board', ['editable' => false, 'lecturer' => true, 'id' => $id, 'kanban' => $kanban])
    </div>

    <!-- KONTRIBUSI PER MAHASISWA -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
        <h3 class="text-sm font-bold text-gray-800 uppercase mb-1">Kontribusi Mahasiswa</h3>
        <p class="text-xs text-gray-500 mb-5">Rekap tugas dan komentar tiap anggota pada proyek ini.</p>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="p-4 text-left">Anggota</th>
                        <th class="p-4 text-center">Ditugaskan</th>
                        <th class="p-4 text-center">Sedang</th>
                        <th class="p-4 text-center">Selesai</th>
                        <th class="p-4 text-center">Belum</th>
                        <th class="p-4 text-center">Komentar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contribution as $row)
                    <tr class="border-t hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ $row['initials'] }}</span>
                                <span class="font-semibold text-gray-800">{{ $row['name'] }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-center font-bold">{{ $row['assigned'] }}</td>
                        <td class="p-4 text-center text-yellow-600">{{ $row['doing'] }}</td>
                        <td class="p-4 text-center text-green-600">{{ $row['done'] }}</td>
                        <td class="p-4 text-center text-gray-500">{{ $row['todo'] }}</td>
                        <td class="p-4 text-center text-blue-600">{{ $row['comments'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-400">Belum ada anggota.</td>
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
