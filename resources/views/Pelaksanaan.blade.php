@extends('layouts.app')

@section('title', 'Pelaksanaan & Evaluasi - DELPRO')

@section('content')
<div class="w-full space-y-6"
     x-data="{
        colAddModal: false,
        colEditModal: false,
        colDeleteModal: false,
        commentModal: false,
        moveModal: false,
        addCol: { label: '', color: 'blue-600', description: '', is_done: false, requires_approval: false, checklist: [] },
        editCol: { id: '', label: '', color: '', description: '', is_done: false, requires_approval: false, checklist: [] },
        delCol: { id: '', label: '' },
        commentTask: { id: '', name: '', comments: [] },
        columns: {{ Illuminate\Support\Js::from($columns) }},
        moveCtx: { id: '', name: '', fromKey: '' },
        moveTarget: '',
        moveChecks: [],
        fAssignee: 'all',
        fDeadline: 'all',
        myId: {{ $currentUserId ?? 0 }},
        openMove(id, name, fromKey) {
            this.moveCtx = { id, name, fromKey };
            this.moveTarget = '';
            this.moveChecks = [];
            this.moveModal = true;
        },
        targetColumn() { return this.columns.find(c => c.key === this.moveTarget) || null; },
        onTargetChange() {
            const t = this.targetColumn();
            const n = (t && t.checklist) ? t.checklist.length : 0;
            this.moveChecks = Array(n).fill(false);
        },
        checklistOk() {
            const t = this.targetColumn();
            if (!t || !t.checklist || t.checklist.length === 0) return true;
            return this.moveChecks.length === t.checklist.length && this.moveChecks.every(v => v === true);
        },
        canMove() { return this.moveTarget !== '' && this.checklistOk(); },
        taskMatch(assigned, days) {
            if (this.fAssignee === 'mine' && String(assigned) !== String(this.myId)) return false;
            if (this.fAssignee !== 'all' && this.fAssignee !== 'mine' && String(assigned) !== String(this.fAssignee)) return false;
            if (this.fDeadline === 'overdue' && !(days !== null && days < 0)) return false;
            if (this.fDeadline === 'urgent' && !(days !== null && days >= 0 && days <= 3)) return false;
            if (this.fDeadline === 'soon' && !(days !== null && days >= 0 && days <= 7)) return false;
            return true;
        }
     }">

    @include('partials.flash-messages')

    <!-- Judul & Breadcrumb -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
            <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                projek saya/ <span class="text-blue-500">Pelaksanaan dan evaluasi</span>
            </nav>
        </div>
        <button @click="addCol = { label: '', color: 'blue-600', description: '', is_done: false, requires_approval: false, checklist: [] }; colAddModal = true"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-1"></i> Tambah Kolom
        </button>
    </div>

    <!-- Anggota Aktif -->
    <div class="flex justify-end">
        <div class="flex -space-x-2">
            @forelse($teamInitials ?? [] as $av)
            <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-blue-600 shadow-sm">{{ $av }}</div>
            @empty
            <span class="text-[10px] text-gray-400 italic">Belum ada anggota</span>
            @endforelse
        </div>
    </div>

    <!-- FILTER -->
    @include('partials.task-filter-bar')

    <!-- KANBAN BOARD -->
    @include('partials.kanban-board', ['editable' => true, 'id' => $id, 'kanban' => $kanban])

    <!-- MODAL TAMBAH KOLOM -->
    <div x-show="colAddModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl max-h-[88vh] overflow-y-auto" @click.outside="colAddModal = false">
            <h3 class="text-lg font-bold mb-5">Tambah Kolom</h3>
            <form method="POST" action="{{ route('pelaksanaan.kolom.tambah', $id) }}">
                @csrf
                @include('partials.kanban-column-fields', ['model' => 'addCol'])
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="colAddModal = false" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KOLOM -->
    <div x-show="colEditModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl max-h-[88vh] overflow-y-auto" @click.outside="colEditModal = false">
            <h3 class="text-lg font-bold mb-5">Ubah Kolom</h3>
            <form method="POST" action="{{ route('pelaksanaan.kolom.edit', $id) }}">
                @csrf
                <input type="hidden" name="column_id" :value="editCol.id">
                @include('partials.kanban-column-fields', ['model' => 'editCol'])
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="colEditModal = false" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HAPUS KOLOM -->
    <div x-show="colDeleteModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 w-full max-w-sm shadow-2xl text-center" @click.outside="colDeleteModal = false">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-500">
                <i class="fas fa-trash"></i>
            </div>
            <h3 class="text-lg font-bold mb-2">Hapus Kolom</h3>
            <p class="text-sm text-gray-500 mb-6">
                Kolom "<span x-text="delCol.label" class="font-semibold"></span>" akan dihapus.
                Tugas di dalamnya dipindahkan ke kolom pertama.
            </p>
            <form method="POST" action="{{ route('pelaksanaan.kolom.hapus', $id) }}">
                @csrf
                <input type="hidden" name="column_id" :value="delCol.id">
                <div class="flex justify-center gap-3">
                    <button type="button" @click="colDeleteModal = false" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-bold">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL PINDAH TUGAS -->
    <div x-show="moveModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl max-h-[88vh] overflow-y-auto" @click.outside="moveModal = false">
            <h3 class="text-lg font-bold mb-1">Pindahkan Tugas</h3>
            <p class="text-xs text-gray-500 mb-4" x-text="moveCtx.name"></p>

            <form method="POST" action="{{ route('pelaksanaan.tugas.pindah', $id) }}">
                @csrf
                <input type="hidden" name="task_id" :value="moveCtx.id">
                <input type="hidden" name="column_key" :value="moveTarget">
                <input type="hidden" name="checklist_confirmed" :value="checklistOk() ? 1 : 0">

                <label class="block text-sm font-semibold text-gray-700 mb-1">Kolom Tujuan</label>
                <select x-model="moveTarget" @change="onTargetChange()" required
                        class="w-full border rounded-xl p-3 mb-4 outline-none focus:border-blue-400">
                    <option value="">Pilih kolom...</option>
                    <template x-for="c in columns.filter(c => c.key !== moveCtx.fromKey)" :key="c.key">
                        <option :value="c.key" x-text="c.label"></option>
                    </template>
                </select>

                {{-- Checklist Definition of Done --}}
                <template x-if="targetColumn() && targetColumn().checklist && targetColumn().checklist.length">
                    <div class="mb-4 rounded-2xl bg-amber-50 border border-amber-100 p-4">
                        <p class="text-xs font-bold text-amber-700 mb-2"><i class="fas fa-list-check mr-1"></i>Definition of Done — centang semua</p>
                        <template x-for="(item, i) in targetColumn().checklist" :key="i">
                            <label class="flex items-start gap-2 text-sm text-gray-700 py-1 cursor-pointer">
                                <input type="checkbox" x-model="moveChecks[i]" class="mt-0.5">
                                <span x-text="item"></span>
                            </label>
                        </template>
                    </div>
                </template>

                {{-- Catatan approval --}}
                <template x-if="targetColumn() && targetColumn().requires_approval">
                    <div class="mb-4 flex items-start gap-2 rounded-xl bg-purple-50 border border-purple-100 p-3 text-xs text-purple-700">
                        <i class="fas fa-user-shield mt-0.5"></i>
                        <span>Kolom ini perlu <b>persetujuan Dosen</b>. Tugas akan menunggu approval sebelum benar-benar pindah.</span>
                    </div>
                </template>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="moveModal = false" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                    <button type="submit" :disabled="!canMove()"
                            :class="canMove() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-300 cursor-not-allowed'"
                            class="px-5 py-2 text-white rounded-xl text-sm font-bold transition">
                        <span x-show="!targetColumn() || !targetColumn().requires_approval">Pindahkan</span>
                        <span x-show="targetColumn() && targetColumn().requires_approval">Ajukan ke Dosen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KOMENTAR -->
    @include('partials.task-comment-modal')
</div>
@endsection
