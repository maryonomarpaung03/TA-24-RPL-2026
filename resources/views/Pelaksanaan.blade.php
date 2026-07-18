@extends('layouts.app')
@section('title', 'Pelaksanaan & Evaluasi - PjBL')
<<<<<<< HEAD

@section('content')
@php
    // Dipakai modal "Pindahkan" untuk menampilkan checklist & peringatan approval
    // kolom tujuan sebelum tugas benar-benar digeser.
    $columnsJs = collect($columns)->map(fn ($c) => [
        'key' => $c['key'],
        'label' => $c['label'],
        'checklist' => $c['checklist'],
        'requires_approval' => $c['requires_approval'],
    ])->values();
@endphp

<div class="w-full space-y-6"
     x-data="{
        columns: @js($columnsJs),

        commentModal: false,
        commentTask: { id: null, name: '', comments: [] },

        moveModal: false,
        moveTask: { id: null, name: '', from: '' },
        moveTo: '',
        checklistConfirmed: false,

        submitModal: false,
        submitTask: { id: null, name: '', type: 'link', link: '', current: null },

        colAddModal: false,
        colEditModal: false,
        colDeleteModal: false,
        addCol: {},
        editCol: {},
        delCol: {},

        get moveTarget() {
            return this.columns.find(c => c.key === this.moveTo) || null;
        },

        openMove(id, name, from) {
            this.moveTask = { id, name, from };
            this.moveTo = '';
            this.checklistConfirmed = false;
            this.moveModal = true;
        },

        openSubmit(id, name, current) {
            this.submitTask = {
                id, name, current,
                type: current && current.kind === 'file' ? 'file' : 'link',
                link: current && current.kind === 'link' ? current.url : '',
            };
            this.submitModal = true;
        },
     }">

    @include('partials.flash-messages')

=======
@section('root_data', '{
                        sidebarOpen: true,
                        commentModal: false,
                        editModal: false,
                        addComment: false,
                        activeColumn:null,
                        selectedTask:{
                            id:null,
                            title:"",
                            status:"pending",
                            link:"",
                            submission_type:"link",
                            attachment_name:"",
                            attachment_url:"",
                            comments:[]},
                        }')
@section('content')
<div x-data="taskManager()"class="w-full space-y-6">
    {{-- ========================================================= --}}
    {{-- HEADER PROJECT --}}
    {{-- ========================================================= --}}
    @include('partials.flash-messages')
    {{-- Status finalisasi proyek hanya ditampilkan di tahap Assessment & Reflection,
         tempat finalisasi itu dikirim; di sini cukup papan tugasnya yang terkunci. --}}
>>>>>>> 19dd9ee (Memperbaiki sistem yang masih error dan malfungsi)
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $namaProjek }}</h2>
        </div>
        <p class="text-xs text-gray-500 max-w-md">
            <i class="fas fa-circle-info text-blue-500 mr-1"></i>
            Seluruh tugas berasal dari tahapan Penyusunan. Di sini tim menggeser tugas antar kolom
            dan mengumpulkan hasilnya — tugas tidak bisa ditambah atau diubah lagi.
        </p>
    </div>
    @if($projectStatus === 'pending_final_revision' && $lastSubmission?->lecturer_note)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
        <p class="text-sm font-bold text-amber-900">
            <i class="fas fa-rotate-left mr-1"></i>Dosen meminta revisi finalisasi
        </p>
        <p class="text-sm text-amber-800 mt-1 whitespace-pre-line">{{ $lastSubmission->lecturer_note }}</p>
        <p class="text-xs text-amber-700 mt-2">
            Perbaiki yang diminta, lalu kirim ulang dari tahapan
            <a href="{{ route('penilaian-individu', $id) }}" class="font-bold underline">Assessment &amp; Reflection</a>.
        </p>
    </div>
    @endif

    {{-- RINGKASAN PROGRES --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
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

    @include('partials.filter-bar', [
        'action' => route('pelaksanaan', $id),
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
<<<<<<< HEAD
    ])

    @if($locked)
    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-3 text-xs font-bold text-gray-500">
        <i class="fas fa-lock mr-1"></i>Papan terkunci — tugas tidak dapat dipindahkan atau dikumpulkan lagi.
    </div>
    @endif

    {{-- PAPAN KANBAN --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6">
        @include('partials.kanban-board', [
            'id' => $id,
            'kanban' => $kanban,
            'canMove' => ! $locked,
            'canSubmit' => ! $locked,
            'canManageColumns' => ! $locked,
        ])
    </div>

    {{-- ========================================================= --}}
    {{-- Modal: Pindahkan tugas --}}
    {{-- ========================================================= --}}
    <div x-show="moveModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="moveModal = false">
        <div @click.outside="moveModal = false"
             class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800">Pindahkan Tugas</h2>
            <p class="text-sm text-gray-500 mt-1" x-text="moveTask.name"></p>

            <form method="POST" :action="`{{ url('projek/'.$id.'/pelaksanaan/tugas') }}/${moveTask.id}/pindah`" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Kolom Tujuan</label>
                    <select name="column_key" x-model="moveTo" required
                            @change="checklistConfirmed = false"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
                        <option value="">— Pilih kolom —</option>
                        <template x-for="c in columns.filter(c => c.key !== moveTask.from)" :key="c.key">
                            <option :value="c.key" x-text="c.label"></option>
                        </template>
                    </select>
                </div>

                {{-- Definition of Done: semua butir wajib dicentang sebelum tugas digeser --}}
                <template x-if="moveTarget && moveTarget.checklist.length">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-bold text-amber-800 mb-2">
                            <i class="fas fa-list-check mr-1"></i>Checklist kolom <span x-text="moveTarget.label"></span>
                        </p>
                        <ul class="space-y-1 mb-3">
                            <template x-for="(item, i) in moveTarget.checklist" :key="i">
                                <li class="text-xs text-amber-900"><i class="fas fa-circle-check mr-1 text-amber-500"></i><span x-text="item"></span></li>
                            </template>
                        </ul>
                        <label class="flex items-start gap-2 text-xs font-bold text-amber-900 cursor-pointer">
                            <input type="checkbox" name="checklist_confirmed" value="1" x-model="checklistConfirmed" class="mt-0.5">
                            Saya menyatakan semua butir di atas sudah dipenuhi.
                        </label>
                    </div>
                </template>

                <template x-if="moveTarget && moveTarget.requires_approval">
                    <p class="rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 text-xs font-bold text-purple-700">
                        <i class="fas fa-user-shield mr-1"></i>
                        Kolom ini butuh persetujuan dosen. Tugas ditahan sampai dosen menyetujui.
                    </p>
                </template>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="moveModal = false"
                            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="!moveTo || (moveTarget && moveTarget.checklist.length && !checklistConfirmed)"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Pindahkan
                    </button>
                </div>
            </form>
        </div>
    </div>

=======
        'extraButton' => null,
    ])
>>>>>>> 19dd9ee (Memperbaiki sistem yang masih error dan malfungsi)
    {{-- ========================================================= --}}
    {{-- Modal: Kumpulkan hasil tugas --}}
    {{-- ========================================================= --}}
<<<<<<< HEAD
    <div x-show="submitModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="submitModal = false">
        <div @click.outside="submitModal = false"
             class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800">Kumpulkan Hasil</h2>
            <p class="text-sm text-gray-500 mt-1" x-text="submitTask.name"></p>

            <form method="POST" enctype="multipart/form-data"
                  :action="`{{ url('projek/'.$id.'/pelaksanaan/tugas') }}/${submitTask.id}/submit`"
                  class="mt-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Bentuk Pengumpulan</label>
                    <select name="submission_type" x-model="submitTask.type"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
                        <option value="link">Link</option>
                        <option value="file">File (foto / dokumen)</option>
                    </select>
                </div>

                <div x-show="submitTask.type === 'link'" x-cloak>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Link Tugas</label>
                    <input type="url" name="link" x-model="submitTask.link"
                           :disabled="submitTask.type !== 'link'"
                           placeholder="https://..."
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
                    <p class="mt-1 text-[11px] text-gray-400">Contoh: tautan Google Drive, GitHub, atau Figma.</p>
                </div>

                <div x-show="submitTask.type === 'file'" x-cloak>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Unggah Berkas</label>
                    <input type="file" name="attachment"
                           :disabled="submitTask.type !== 'file'"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white file:font-semibold hover:file:bg-blue-700">
                    <p class="mt-1 text-[11px] text-gray-400">
                        Foto (JPG/PNG/WEBP/GIF) atau dokumen (PDF/DOC/XLS/PPT/TXT/ZIP), maks. 10 MB.
                    </p>

                    <template x-if="submitTask.current && submitTask.current.kind === 'file'">
                        <p class="mt-2 text-xs text-gray-500">
                            Berkas saat ini:
                            <a :href="submitTask.current.url" target="_blank"
                               class="font-bold text-blue-600 hover:underline" x-text="submitTask.current.label"></a>
                            <span class="text-gray-400">— unggah berkas baru untuk menggantinya.</span>
                        </p>
                    </template>
                </div>

                <p class="rounded-xl bg-gray-50 px-4 py-3 text-[11px] text-gray-500">
                    Mengganti hasil yang sudah direview dosen akan membatalkan tanda review tersebut.
                </p>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="submitModal = false"
                            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-1"></i>Kumpulkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- Modal: Komentar tugas (satu thread dengan dosen) --}}
    {{-- ========================================================= --}}
    <div x-show="commentModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="commentModal = false">
        <div @click.outside="commentModal = false"
             class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Komentar Tugas</h2>
                    <p class="text-sm text-gray-500 mt-1" x-text="commentTask.name"></p>
                </div>
                <button type="button" @click="commentModal = false" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="max-h-64 overflow-y-auto space-y-2 mb-5 pr-1">
                <template x-if="!commentTask.comments || commentTask.comments.length === 0">
                    <p class="text-sm text-gray-400 italic py-2">Belum ada komentar pada tugas ini.</p>
                </template>

                <template x-for="(c, i) in (commentTask.comments || [])" :key="i">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
                        <div class="flex items-center justify-between gap-2 text-[11px]">
                            <span class="font-bold text-gray-700" x-text="c.from"></span>
                            <span class="shrink-0 text-gray-400" x-text="c.time"></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-0.5 whitespace-pre-line" x-text="c.text"></p>
                    </div>
                </template>
            </div>

            <form method="POST" :action="`{{ url('projek/'.$id.'/tugas') }}/${commentTask.id}/komentar`">
                @csrf
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Tulis Komentar</label>
                <textarea rows="4" name="komentar" required maxlength="2000"
                          placeholder="Masukkan komentar..."
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none resize-none focus:border-blue-500"></textarea>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="commentModal = false"
                            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-1"></i>Kirim Komentar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @unless($locked)
    {{-- ========================================================= --}}
    {{-- Modal: Tambah & Ubah kolom papan --}}
    {{-- ========================================================= --}}
    @foreach([
        ['open' => 'colAddModal', 'model' => 'addCol', 'title' => 'Tambah Kolom', 'submit' => 'Simpan Kolom'],
        ['open' => 'colEditModal', 'model' => 'editCol', 'title' => 'Ubah Kolom', 'submit' => 'Simpan Perubahan'],
    ] as $modal)
    <div x-show="{{ $modal['open'] }}" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="{{ $modal['open'] }} = false">
        <div @click.outside="{{ $modal['open'] }} = false"
             class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $modal['title'] }}</h2>

            <form method="POST"
                  :action="'{{ url('projek/'.$id.'/pelaksanaan/kolom') }}' + ({{ $modal['model'] }}.id ? '/' + {{ $modal['model'] }}.id : '')"
                  class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Nama Kolom</label>
                    <input type="text" name="label" x-model="{{ $modal['model'] }}.label" required maxlength="60"
                           placeholder="Mis. Review, Testing, Siap Rilis"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Keterangan <span class="text-gray-300">(opsional)</span></label>
                    <input type="text" name="description" x-model="{{ $modal['model'] }}.description" maxlength="255"
                           placeholder="Kapan tugas boleh masuk kolom ini?"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Warna</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($colorOptions as $color)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{ $color }}" x-model="{{ $modal['model'] }}.color" class="sr-only peer">
                            <span class="block w-8 h-8 rounded-full bg-{{ $color }} ring-offset-2 peer-checked:ring-2 ring-gray-800 transition"></span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2 rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="is_done" value="1" x-model="{{ $modal['model'] }}.is_done">
                        Kolom ini berarti tugas <span class="font-bold">Selesai</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="requires_approval" value="1" x-model="{{ $modal['model'] }}.requires_approval">
                        Butuh persetujuan dosen sebelum tugas masuk
                    </label>
                </div>

                {{-- Definition of Done kolom ini --}}
                <div x-data="{ items: {{ $modal['model'] }}.checklist && {{ $modal['model'] }}.checklist.length ? {{ $modal['model'] }}.checklist : [''] }"
                     x-effect="items = {{ $modal['model'] }}.checklist && {{ $modal['model'] }}.checklist.length ? {{ $modal['model'] }}.checklist : ['']">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Checklist <span class="text-gray-300">(opsional, maks. 15)</span>
                    </label>
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" name="checklist[]" x-model="items[i]" maxlength="120"
                                   placeholder="Mis. Sudah direview rekan tim"
                                   class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-none focus:border-blue-500">
                            <button type="button" @click="items.splice(i, 1)" x-show="items.length > 1"
                                    class="text-gray-300 hover:text-red-500 px-2">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="items.length < 15 && items.push('')"
                            class="text-xs font-bold text-blue-600 hover:underline">+ Tambah butir</button>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="{{ $modal['open'] }} = false"
                            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        {{ $modal['submit'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Modal: Hapus kolom --}}
    <div x-show="colDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="colDeleteModal = false">
        <div @click.outside="colDeleteModal = false"
             class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-8">
            <h2 class="text-2xl font-bold text-gray-800">Hapus Kolom</h2>
            <p class="text-sm text-gray-500 mt-2">
                Kolom <span class="font-bold text-gray-800" x-text="delCol.label"></span> akan dihapus.
                Tugas di dalamnya <span class="font-bold">tidak ikut terhapus</span> — semuanya dipindahkan ke kolom tersisa.
            </p>

            <form method="POST" :action="`{{ url('projek/'.$id.'/pelaksanaan/kolom') }}/${delCol.id}`" class="mt-6">
                @csrf
                @method('DELETE')
                <div class="flex justify-end gap-3">
                    <button type="button" @click="colDeleteModal = false"
                            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-red-600 text-white font-semibold hover:bg-red-700">
                        Hapus Kolom
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endunless

</div>
@endsection
=======
    <div class="flex gap-6 overflow-x-auto pb-4 w-full">
@foreach($boards as $board)
@php
$totalTask = $board->tasks->count();
$completedTask = $board->tasks->where('status','completed')->count();
$progress = $totalTask ? round(($completedTask/$totalTask)*100) : 0;
@endphp
<div class="flex-1 basis-0 min-w-[280px] bg-white border border-gray-100 rounded-3xl shadow-md p-5 flex flex-col">
    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <h3 class="font-bold">{{ $board->name }}</h3>
        </div>
        <span class="text-xs text-gray-500">
            {{ $totalTask }} Task
        </span>
    </div>
    {{-- Progress --}}
    <div class="mb-5">
        <div class="flex justify-between text-xs mb-1">
            <span>{{ $completedTask }}/{{ $totalTask }}</span>
            <span>{{ $progress }}%</span>
        </div>
        <div class="w-full h-2 rounded-full bg-gray-200">
            <div
                class="h-2 rounded-full bg-blue-500"
                style="width: {{ $progress }}%">
            </div>
        </div>
    </div>
    {{-- TASK LIST --}}
    <div
        class="task-list flex-1 space-y-3"
        data-board-id="{{ $board->id }}"
    >
        @forelse($board->tasks as $task)
        @php $taskComments = $comments[$task->id] ?? []; @endphp
        <div
            data-task-id="{{ $task->id }}"
            class="task-card bg-white rounded-2xl shadow-sm p-4 border border-gray-200 hover:shadow-md transition">
            <div class="flex justify-between">
                <h4 class="font-semibold">
                    {{ $task->task_title }}
                </h4>
                <div class="flex items-start gap-2 shrink-0">
                @unless($locked)
                <button
    @click="
        editModal = true;
        selectedTask = {
            id: {{ $task->id }},
            title: @js($task->task_title),
            link: @js($task->link),
            submission_type: @js($task->submission_type ?: ($task->attachment_path ? 'file' : 'link')),
            attachment_name: @js($task->attachment_name),
            attachment_url: @js($task->attachment_path ? asset('storage/'.$task->attachment_path) : null),
            status: '{{ $task->status }}',
            comments: []
        }
    "
    class="text-gray-400 hover:text-blue-600"
>
    <i class="fas fa-edit"></i>
</button>
                @endunless
<button
    @click="
        addComment=true;
        selectedTask.id={{ $task->id }};
        selectedTask.title=@js($task->task_title);
        selectedTask.comments=@js($taskComments);
    "
    class="relative text-blue-600 hover:text-blue-800">
    <i class="fas fa-comment"></i>
    @if(count($taskComments))
    <span class="absolute -top-1.5 -right-2 inline-flex min-w-[14px] h-3.5 items-center justify-center rounded-full bg-blue-600 px-1 text-[8px] font-bold text-white">{{ count($taskComments) }}</span>
    @endif
</button>
                </div>
            </div>
            @if($task->description)
                <p class="text-xs text-gray-500 mt-2">
                    {{ $task->description }}
                </p>
            @endif
            {{-- Dosen sudah meninjau pengumpulan tugas ini --}}
            @if($task->reviewed_at)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                    <i class="fas fa-circle-check"></i>
                    Sudah direview dosen
                </p>
            @endif
            {{-- HASIL SUBMIT: link atau berkas --}}
            @if($task->attachment_path)
                <a href="{{ asset('storage/'.$task->attachment_path) }}" target="_blank"
                   class="mt-2 inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-100">
                    <i class="fas {{ str_starts_with((string) $task->attachment_mime, 'image/') ? 'fa-image' : 'fa-file-alt' }}"></i>
                    <span class="truncate">{{ $task->attachment_name }}</span>
                </a>
            @elseif($task->link)
                <a href="{{ $task->link }}" target="_blank"
                   class="mt-2 inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-100">
                    <i class="fas fa-link"></i>
                    <span class="truncate">Link tugas</span>
                </a>
            @endif
            {{-- KOMENTAR: satu thread berisi anggota tim & dosen --}}
@if(count($taskComments))
<div class="mt-3 space-y-2">
    @foreach(array_slice($taskComments, -3) as $comment)
    <div class="flex gap-2">
        <div class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-[9px] font-bold text-white {{ $comment['is_lecturer'] ? 'bg-purple-600' : 'bg-blue-500' }}">
            {{ $comment['initials'] }}
        </div>
        <div class="flex-1 rounded-xl px-3 py-2 {{ $comment['is_lecturer'] ? 'bg-purple-50 border border-purple-100' : 'bg-gray-100' }}">
            <p class="text-[10px] font-bold {{ $comment['is_lecturer'] ? 'text-purple-700' : 'text-gray-600' }}">
                {{ $comment['from'] }}
            </p>
            <p class="text-xs text-gray-700">
                {{ $comment['text'] }}
            </p>
            <small class="text-gray-400">
                {{ $comment['time'] }}
            </small>
        </div>
    </div>
    @endforeach
    @if(count($taskComments) > 3)
    <button type="button"
            @click="addComment=true; selectedTask.id={{ $task->id }}; selectedTask.title=@js($task->task_title); selectedTask.comments=@js($taskComments);"
            class="text-[11px] font-bold text-blue-600 hover:underline">
        Lihat semua {{ count($taskComments) }} komentar
    </button>
    @endif
</div>
@endif
            <div class="flex justify-between mt-4">
                @php
                    $priorityTone = match (strtolower((string) $task->priority)) {
                        'low' => 'bg-blue-100 text-blue-700',
                        'high' => 'bg-red-100 text-red-700',
                        default => 'bg-yellow-100 text-yellow-700',
                    };
                @endphp
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $priorityTone }}">
                    {{ strtoupper($task->priority) }}
                </span>
                <span class="text-xs text-gray-500">
                    <i class="fas fa-calendar mr-1"></i>{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'Tanpa tenggat' }}
                </span>
            </div>
        </div>
        @empty
        <div class="rounded-xl border-2 border-dashed border-gray-300 p-6 text-center text-gray-400 text-sm">
            Belum ada task
        </div>
        @endforelse
    </div>
</div>
@endforeach
</div>
    {{-- ========================================================= --}}
    {{-- Modal Edit --}}
    {{-- ========================================================= --}}
<div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div @click.outside="editModal=false" class="w-full max-w-xl rounded-[2rem] bg-white p-8 shadow-2xl">
        <div class="mb-8 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-800">Edit Task</h2>
            <button type="button" @click="editModal=false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" :action="'{{ url('/tasks') }}/' + selectedTask.id + '/update'" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Nama Task</label>
                <input type="text" name="task_title" x-model="selectedTask.title" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Submit Tugas
                        </label>
                        <select
                            name="submission_type"
                            x-model="selectedTask.submission_type"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none focus:border-blue-500">
                            <option value="link">Link</option>
                            <option value="file">File (foto / dokumen)</option>
                        </select>
                    </div>
                    {{-- Pilihan: Link --}}
                    <div x-show="selectedTask.submission_type === 'link'" x-cloak>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Link Tugas
                        </label>
                        <input
                            type="url"
                            name="link"
                            x-model="selectedTask.link"
                            :disabled="selectedTask.submission_type !== 'link'"
                            placeholder="https://..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none focus:border-blue-500">
                        <p class="mt-1 text-[11px] text-gray-400">
                            Contoh: tautan Google Drive, GitHub, atau Figma.
                        </p>
                    </div>
                    {{-- Pilihan: File --}}
                    <div x-show="selectedTask.submission_type === 'file'" x-cloak>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Unggah Berkas
                        </label>
                        <input
                            type="file"
                            name="attachment"
                            :disabled="selectedTask.submission_type !== 'file'"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white file:font-semibold hover:file:bg-blue-700">
                        <p class="mt-1 text-[11px] text-gray-400">
                            Foto (JPG/PNG/WEBP/GIF) atau dokumen (PDF/DOC/XLS/PPT/TXT/ZIP), maks. 10 MB.
                        </p>
                        <template x-if="selectedTask.attachment_url">
                            <p class="mt-2 text-xs text-gray-500">
                                Berkas saat ini:
                                <a :href="selectedTask.attachment_url" target="_blank"
                                   class="font-bold text-blue-600 hover:underline"
                                   x-text="selectedTask.attachment_name"></a>
                                <span class="text-gray-400">â€” unggah berkas baru untuk menggantinya.</span>
                            </p>
                        </template>
                    </div>
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Status</label>
                <select name="status" x-model="selectedTask.status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                    <option value="pending">To Do</option>
                    <option value="in_progress">On Progress</option>
                    <option value="completed">Complete</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button
                    type="button"
                    @click="editModal=false"
                    class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                    Batal
                </button>
                <button
                    type="submit"
                    class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
{{-- ========================================================= --}}
{{-- Modal Add Comment --}}
{{-- ========================================================= --}}
<div x-show="addComment"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div
        @click.outside="addComment=false"
        class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">
        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    Komentar Tugas
                </h2>
                <p class="text-sm text-gray-500 mt-1"
                   x-text="selectedTask.title">
                </p>
            </div>
            <button
                @click="addComment=false"
                class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        {{-- Riwayat komentar: anggota tim & dosen dalam satu thread --}}
        <div class="max-h-64 overflow-y-auto space-y-2 mb-5 pr-1">
            <template x-if="!selectedTask.comments || selectedTask.comments.length === 0">
                <p class="text-sm text-gray-400 italic py-2">Belum ada komentar pada tugas ini.</p>
            </template>
            <template x-for="(c, i) in (selectedTask.comments || [])" :key="i">
                <div class="rounded-xl px-3 py-2 border"
                     :class="c.is_lecturer ? 'bg-purple-50 border-purple-100' : 'bg-gray-50 border-gray-100'">
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <span class="font-bold" :class="c.is_lecturer ? 'text-purple-700' : 'text-gray-700'" x-text="c.from"></span>
                        <span class="shrink-0 text-gray-400" x-text="c.time"></span>
                    </div>
                    <p class="text-sm text-gray-700 mt-0.5 whitespace-pre-line" x-text="c.text"></p>
                </div>
            </template>
        </div>
        {{-- FORM: komentar masuk ke thread yang sama dengan komentar dosen --}}
        <form
    method="POST"
    :action="`{{ url('projek/'.$id.'/tugas') }}/${selectedTask.id}/komentar`">
    @csrf
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
            Tulis Komentar
        </label>
        <textarea
            rows="4"
            name="komentar"
            required
            maxlength="2000"
            placeholder="Masukkan komentar..."
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none resize-none focus:border-blue-500">
        </textarea>
    </div>
    <div class="flex justify-end gap-3 mt-6">
        <button
            type="button"
            @click="addComment=false"
            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
            Batal
        </button>
        <button
            type="submit"
            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
            <i class="fas fa-paper-plane mr-1"></i>Kirim Komentar
        </button>
    </div>
</form>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function taskManager(){ return {}; }
document.addEventListener("DOMContentLoaded",function(){
    // Proyek yang sudah difinalisasi terkunci: tugas tidak boleh dipindah lagi.
    @if($locked)
        return;
    @endif
    document.querySelectorAll(".task-list").forEach(function(board){
        new Sortable(board,{
            group:"kanban",
            animation:180,
            ghostClass:"opacity-50",
            draggable:".task-card",
            onEnd:function(evt){
                let taskId=evt.item.dataset.taskId;
                let boardId=evt.to.dataset.boardId;
                fetch("{{ route('tasks.move') }}",{
                    method:"POST",
                    headers:{
                        "Content-Type":"application/json",
                        "Accept":"application/json",
                        "X-CSRF-TOKEN":"{{ csrf_token() }}"
                    },
                    body:JSON.stringify({
                        task_id:taskId,
                        board_id:boardId
                    })
                })
                .then(r=>r.json())
                .then(data=>{
                    if(data.success){
                        location.reload();
                    }
                });
            }
        });
    });
});
</script>
@endpush
>>>>>>> 19dd9ee (Memperbaiki sistem yang masih error dan malfungsi)
