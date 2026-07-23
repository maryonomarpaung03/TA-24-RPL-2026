@extends('layouts.app')

@section('title', 'Pelaksanaan & Evaluasi - PjBL')

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

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $namaProjek }}</h2>
            <p class="text-[11px] uppercase tracking-wider text-gray-400 mt-2">Projek Saya /
                <span class="text-blue-600">Pelaksanaan & Evaluasi</span>
            </p>
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
                            :disabled="!moveTo"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Pindahkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- Modal: Kumpulkan hasil tugas --}}
    {{-- ========================================================= --}}
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

</div>
@endsection
