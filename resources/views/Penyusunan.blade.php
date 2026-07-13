@extends('layouts.app')

@section('title', 'Penyusunan Rencana - PjBL')
@section('root_data', "{ sidebarOpen: true }")

@section('content')
<div class="w-full"
x-data="{
    addModal:false,
    commentModal:false,
    editModal:false,
    deleteModal:false,
    confirmEdit:false,
    selectedTask:{id:'',judul:'',deskripsi:'',mulai:'',selesai:'',pj:''},
    myId: {{ $currentUserId ?? 0 }}
}">

<div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
        <p class="text-gray-400 text-xs mt-1">Penyusunan Rencana Proyek</p>
    </div>

    <button
        @click="addModal = true"
        class="bg-blue-600 text-white px-5 py-2 rounded-xl font-bold hover:bg-blue-700 transition"
    >
        + Tambah Tugas
    </button>
</div>

@include('partials.due-today-alert')

@include('partials.filter-bar', [
    'action' => route('penyusunan', $id),
    'filters' => [
        ['name' => 'status', 'label' => 'Status', 'value' => $filterState['status'], 'options' => $statusOptions],
        ['name' => 'pj', 'label' => 'Penanggung Jawab', 'value' => $filterState['pj'], 'options' => $pjOptions],
        ['name' => 'tenggat', 'label' => 'Tenggat', 'value' => $filterState['tenggat'], 'options' => $tenggatOptions],
    ],
    'summary' => 'Menampilkan '.count($tasks).' dari '.$totalTasks.' tugas.',
    'extraAction' => [
        'url' => route('penyusunan', ['id' => $id, 'pj' => $currentUserId]),
        'label' => 'Tampilkan tugas saya',
        'icon' => 'fas fa-user',
    ],
])

<div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
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
                    <th class="p-4 text-center">Alat Bantu</th>
                </tr>
            </thead>

            <tbody>
                @forelse($tasks as $task)
                @php $isMine = (int) $task['assigned_to'] === (int) ($currentUserId ?? 0); @endphp
                <tr class="border-t hover:bg-gray-50 transition {{ $isMine ? 'bg-blue-50/40' : '' }}">
                    <td class="p-4 text-center">{{ $task['no'] }}</td>
                    <td class="p-4 font-bold">{{ $task['judul'] }}</td>
                    <td class="p-4 text-gray-500">{{ $task['deskripsi'] }}</td>
                    <td class="p-4 text-center">{{ $task['mulai'] }}</td>
                    <td class="p-4 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span>{{ $task['selesai'] }}</span>
                            @if(!empty($task['urgency_label']))
                            @php
                                $urgencyClass = match ($task['urgency']) {
                                    'overdue' => 'bg-red-100 text-red-600',
                                    'today' => 'bg-rose-600 text-white',
                                    'urgent' => 'bg-orange-100 text-orange-600',
                                    default => 'bg-amber-100 text-amber-600',
                                };
                            @endphp
                            <span class="text-[9px] px-2 py-0.5 rounded-full font-bold {{ $urgencyClass }}">
                                <i class="fas fa-clock mr-0.5"></i>{{ $task['urgency_label'] }}
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="p-4 text-center font-semibold">
                        {{ $task['pj'] }}
                        @if($isMine)
                        <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded-full font-bold bg-blue-100 text-blue-600 align-middle">Saya</span>
                        @endif
                    </td>
                    <td class="p-4 text-center">
                        @php
                            $toneClass = match ($task['status']['tone']) {
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
                    <td class="p-4">
                        <div class="flex justify-center gap-4">

                            <button
                                @click="commentModal = true;
                                selectedTask = {
                                    id:'{{ $task['id'] }}',
                                    judul:@js($task['judul']),
                                    deskripsi:@js($task['deskripsi']),
                                    comments:@js($task['comments'])
                                }"
                                class="relative text-blue-500 hover:text-blue-700"
                                title="Komentar tugas"
                            >
                                <i class="fas fa-comment-dots"></i>
                                @if(count($task['comments']) > 0)
                                <span class="absolute -top-2 -right-2 inline-flex min-w-[16px] h-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[9px] font-bold text-white">{{ count($task['comments']) }}</span>
                                @endif
                            </button>

                            <button
                                @click="editModal = true;
                                selectedTask = {
                                    id:'{{ $task['id'] }}',
                                    judul:@js($task['judul']),
                                    deskripsi:@js($task['deskripsi']),
                                    mulai:'{{ $task['mulai'] }}',
                                    selesai:'{{ $task['selesai'] }}'
                                }"
                                class="text-yellow-500 hover:text-yellow-700"
                            >
                                <i class="fas fa-pencil-alt"></i>
                            </button>

                            <button
                                @click="deleteModal = true;
                                selectedTask = {
                                    id:'{{ $task['id'] }}',
                                    judul:@js($task['judul'])
                                }"
                                class="text-red-500 hover:text-red-700"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-10 text-gray-400">
                        {{ $totalTasks > 0 ? 'Tidak ada tugas yang cocok dengan filter.' : 'Belum ada tugas.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- TAMBAH -->
<div x-show="addModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-cloak>
<div class="bg-white rounded-3xl p-8 w-full max-w-2xl">
<h3 class="font-bold text-xl mb-5">Tambah Tugas</h3>

<form action="{{ route('penyusunan.tambah-tugas', $id) }}" method="POST">
@csrf
<div class="space-y-4">
<div>
<label class="block text-sm font-semibold text-gray-700 mb-1">Nama Tugas</label>
<input type="text" name="judul_tugas" placeholder="Judul tugas" class="w-full border rounded-xl p-3" required>
</div>
<div>
<label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Tugas</label>
<textarea name="deskripsi_tugas" placeholder="Deskripsi tugas" class="w-full border rounded-xl p-3"></textarea>
</div>
<div class="grid grid-cols-2 gap-4">
<div>
<label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Mulai</label>
<input type="date" name="tanggal_mulai" class="w-full border rounded-xl p-3" required>
</div>
<div>
<label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Berakhir</label>
<input type="date" name="tanggal_selesai" class="w-full border rounded-xl p-3" required>
</div>
</div>
<div>
<label class="block text-sm font-semibold text-gray-700 mb-1">Nama Penanggung Jawab</label>
<select name="penanggung_jawab" class="w-full border rounded-xl p-3" required>
<option value="">Pilih Penanggung Jawab</option>
@foreach($users as $user)
<option value="{{ $user->id }}">{{ $user->full_name }}</option>
@endforeach
</select>
</div>
</div>
<div class="flex justify-end gap-3 mt-6">
<button type="button" @click="addModal=false" class="px-5 py-2 bg-gray-200 rounded-xl">Batal</button>
<button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl">Tambah</button>
</div>
</form>
</div>
</div>

<!-- KOMENTAR -->
<div x-show="commentModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-cloak>
<div class="bg-white rounded-3xl p-8 w-full max-w-xl">
<h3 class="font-bold text-xl mb-4">Komentar Tugas</h3>
<p class="font-semibold" x-text="selectedTask.judul"></p>
<p class="text-sm text-gray-500 mb-4" x-text="selectedTask.deskripsi"></p>

<!-- Daftar komentar -->
<div class="mb-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">
        Komentar (<span x-text="(selectedTask.comments || []).length"></span>)
    </p>
    <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
        <template x-if="!selectedTask.comments || selectedTask.comments.length === 0">
            <p class="text-sm text-gray-400 italic py-2">Belum ada komentar pada tugas ini.</p>
        </template>
        <template x-for="(c, i) in (selectedTask.comments || [])" :key="i">
            <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2">
                <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500">
                    <span class="font-semibold text-gray-700" x-text="c.from"></span>
                    <span class="shrink-0" x-text="c.time"></span>
                </div>
                <p class="text-sm text-gray-700 mt-0.5 whitespace-pre-line" x-text="c.text"></p>
            </div>
        </template>
    </div>
</div>

<form action="{{ route('penyusunan.komentar-tugas', $id) }}" method="POST">
@csrf
<input type="hidden" name="task_id" :value="selectedTask.id">
<textarea name="komentar" class="w-full border rounded-xl p-3" rows="4" required placeholder="Tulis komentar..."></textarea>
<div class="flex justify-end gap-3 mt-5">
<button type="button" @click="commentModal=false" class="px-5 py-2 bg-gray-200 rounded-xl">Batal</button>
<button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl">Komentar</button>
</div>
</form>
</div>
</div>

<!-- EDIT -->
<div x-show="editModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-cloak>
<div class="bg-white rounded-3xl p-8 w-full max-w-2xl">
<h3 class="font-bold text-xl mb-5">Edit Tugas</h3>
<form @submit.prevent="confirmEdit=true">
<input type="hidden" id="editTaskId" :value="selectedTask.id">
<div class="space-y-4">
<input x-model="selectedTask.judul" type="text" class="w-full border rounded-xl p-3" required>
<textarea x-model="selectedTask.deskripsi" class="w-full border rounded-xl p-3"></textarea>
<div class="grid grid-cols-2 gap-4">
<input x-model="selectedTask.mulai" type="date" class="border rounded-xl p-3">
<input x-model="selectedTask.selesai" type="date" class="border rounded-xl p-3">
</div>
</div>
<div class="flex justify-end gap-3 mt-6">
<button type="button" @click="editModal=false" class="px-5 py-2 bg-gray-200 rounded-xl">Batal</button>
<button type="submit" class="px-5 py-2 bg-yellow-500 text-white rounded-xl">Edit</button>
</div>
</form>
</div>
</div>

<!-- KONFIRMASI EDIT -->
<div x-show="confirmEdit" class="fixed inset-0 bg-black/40 flex items-center justify-center z-[60]" x-cloak>
<div class="bg-white rounded-3xl p-8 max-w-md w-full text-center">
<h3 class="font-bold text-lg mb-4">Apakah kamu yakin ingin mengedit tugas ini?</h3>
<form action="{{ route('penyusunan.edit-tugas', $id) }}" method="POST">
@csrf
<input type="hidden" name="task_id" :value="selectedTask.id">
<input type="hidden" name="judul_tugas" :value="selectedTask.judul">
<input type="hidden" name="deskripsi_tugas" :value="selectedTask.deskripsi">
<input type="hidden" name="tanggal_mulai" :value="selectedTask.mulai">
<input type="hidden" name="tanggal_selesai" :value="selectedTask.selesai">
<div class="flex gap-3 mt-4">
<button type="button" @click="confirmEdit=false" class="flex-1 bg-gray-200 rounded-xl py-2">Batal</button>
<button type="submit" class="flex-1 bg-yellow-500 text-white rounded-xl py-2">Konfirmasi</button>
</div>
</form>
</div>
</div>

<!-- HAPUS -->
<div x-show="deleteModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-cloak>
<div class="bg-white rounded-3xl p-8 max-w-md w-full text-center">
<h3 class="font-bold text-lg mb-3">Apakah kamu yakin ingin menghapus tugas ini?</h3>
<p class="text-gray-500 mb-5" x-text="selectedTask.judul"></p>
<form action="{{ route('penyusunan.hapus-tugas', $id) }}" method="POST">
@csrf
<input type="hidden" name="task_id" :value="selectedTask.id">
<div class="flex gap-3">
<button type="button" @click="deleteModal=false" class="flex-1 bg-gray-200 rounded-xl py-2">Batal</button>
<button type="submit" class="flex-1 bg-red-600 text-white rounded-xl py-2">Yakin</button>
</div>
</form>
</div>
</div>

@if(session('success'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-4 rounded-2xl shadow-xl z-[100]">
{{ session('success') }}
</div>
@endif

</div>
@endsection
