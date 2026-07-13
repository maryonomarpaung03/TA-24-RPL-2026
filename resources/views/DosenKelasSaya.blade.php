@extends('layouts.app')

@section('title', 'Kelas Saya - PjBL')
@section('root_data', '{ sidebarOpen: true }')

@section('content')
<div class="w-full space-y-6" x-data="{ editId: null }">

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Dosen</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">Kelas Saya</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola kelas yang Anda buat: buka ruang kelas, ubah informasi, atau hapus.</p>
    </div>

    @include('partials.flash-messages')

    @if($totalClasses > 0)
        @include('partials.filter-bar', [
            'action' => route('dosen.kelas'),
            'search' => [
                'name' => 'q',
                'value' => $filterState['q'],
                'placeholder' => 'Cari nama kelas, mata kuliah, atau kode',
            ],
            'filters' => [
                ['name' => 'jurusan', 'label' => 'Jurusan', 'value' => $filterState['jurusan'], 'options' => $jurusanOptions],
                ['name' => 'semester', 'label' => 'Semester', 'value' => $filterState['semester'], 'options' => $semesterOptions],
                ['name' => 'tahun', 'label' => 'Tahun Akademik', 'value' => $filterState['tahun'], 'options' => $tahunOptions],
            ],
            'summary' => 'Menampilkan '.$classes->count().' dari '.$totalClasses.' kelas.',
        ])
    @endif

    @if($classes->isEmpty())
        <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-12 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <i class="fas fa-chalkboard-teacher text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-slate-700">
                {{ $totalClasses > 0 ? 'Tidak ada kelas yang cocok dengan filter.' : 'Belum ada kelas.' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $totalClasses > 0 ? 'Coba ubah kata kunci atau reset filter.' : 'Buat kelas baru lewat menu Kelas di sidebar.' }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($classes as $class)
            @php $classUnread = ($unreadMap[$class->id] ?? ['chat' => 0, 'projects' => 0, 'total' => 0]); @endphp
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-slate-900 truncate">{{ $class->name }}</h3>
                        <p class="text-sm text-slate-500 truncate">{{ $class->course_name }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($classUnread['total'] > 0)
                        <span class="inline-flex min-w-[20px] h-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold text-white"
                              title="{{ $classUnread['chat'] }} chat & {{ $classUnread['projects'] }} proyek baru">{{ $classUnread['total'] > 99 ? '99+' : $classUnread['total'] }}</span>
                        @endif
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide
                            {{ $class->visibility === 'closed' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $class->visibility === 'closed' ? 'Closed' : 'Public' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                    @if(!empty($class->academic_year))
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ $class->academic_year }}</span>
                    @endif
                    @if(!empty($class->semester))
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ $class->semester }}</span>
                    @endif
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">
                        <i class="fas fa-users"></i> {{ $class->members_count }} mahasiswa
                    </span>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-semibold">Kode Kelas</p>
                        <p class="font-mono text-base font-bold tracking-widest text-slate-900">{{ $class->join_code }}</p>
                    </div>
                    <button type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $class->join_code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        <span x-show="!copied"><i class="fas fa-copy"></i> Salin</span>
                        <span x-show="copied" x-cloak class="text-emerald-600"><i class="fas fa-check"></i> Tersalin</span>
                    </button>
                </div>

                <div class="mt-4 flex items-center gap-2 pt-4 border-t border-slate-100">
                    <a href="{{ route('classes.show', $class->id) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
                        <i class="fas fa-door-open"></i> Buka Kelas
                    </a>
                    <button type="button" @click="editId = {{ $class->id }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            title="Edit kelas">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form action="{{ route('dosen.classes.destroy', $class->id) }}" method="POST"
                          onsubmit="return confirm('Hapus kelas &quot;{{ $class->name }}&quot;? Semua anggota & chat akan ikut terhapus.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                                title="Hapus kelas">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>

                {{-- Modal edit kelas --}}
                <template x-teleport="body">
                <div x-show="editId === {{ $class->id }}" x-cloak
                     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60"
                     @click.self="editId = null" @keydown.escape.window="editId = null">
                    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl max-h-[92vh] overflow-y-auto" @click.stop>
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                            <h3 class="font-bold text-slate-900 text-lg">Edit Kelas</h3>
                            <button type="button" @click="editId = null" class="text-slate-400 hover:text-slate-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form action="{{ route('dosen.classes.update', $class->id) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Kelas</label>
                                <input type="text" name="name" value="{{ old('name', $class->name) }}" required
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Mata Kuliah</label>
                                <input type="text" name="course_name" value="{{ old('course_name', $class->course_name) }}" required
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tahun Ajaran</label>
                                    <input type="text" name="academic_year" value="{{ old('academic_year', $class->academic_year) }}" required
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Semester</label>
                                    <select name="semester" required
                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm bg-white focus:border-blue-400 focus:outline-none">
                                        @foreach (['Ganjil', 'Genap', 'Pendek'] as $sem)
                                        <option value="{{ $sem }}" @selected(old('semester', $class->semester) === $sem)>{{ $sem }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kapasitas Maks.</label>
                                    <input type="number" name="max_members" value="{{ old('max_members', $class->max_members) }}" min="1" max="500"
                                           placeholder="Opsional"
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tipe Akses</label>
                                    <select name="visibility" required
                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm bg-white focus:border-blue-400 focus:outline-none">
                                        <option value="public" @selected(old('visibility', $class->visibility) === 'public')>Public Room</option>
                                        <option value="closed" @selected(old('visibility', $class->visibility) === 'closed')>Closed Room</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                                <textarea name="description" rows="3"
                                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">{{ old('description', $class->description) }}</textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                                <button type="button" @click="editId = null"
                                        class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="rounded-full bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-blue-700 shadow-sm">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                </template>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
