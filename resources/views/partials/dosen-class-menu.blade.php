{{-- Menu tambah kelas dosen --}}
@php
    use App\Http\Controllers\LecturerClassController;

    $openCreateClassModal = $errors->hasAny([
        'fakultas', 'jurusan', 'name', 'course_name', 'academic_year', 'semester', 'description',
        'max_members', 'custom_join_code', 'visibility',
        'invite_lecturer_emails', 'invite_lecturer_emails.*',
        'invite_student_emails', 'invite_student_emails.*',
    ]);
    $lecturersForInvite = LecturerClassController::lecturersForInvite();
    $studentsForInvite = LecturerClassController::studentsForInvite();
    $currentYear = (int) date('Y');
    $defaultAcademicYear = $currentYear.'/'.($currentYear + 1);
@endphp
<div class="relative" x-data="{ menuOpen: false, modal: @json($openCreateClassModal ? 'create' : null) }" @keydown.escape.window="menuOpen = false; modal = null">

    <button type="button"
            @click="menuOpen = !menuOpen"
            class="flex w-full items-center gap-3 p-3 rounded-lg transition bg-blue-600 text-white hover:bg-blue-700 shadow-sm"
            :class="menuOpen ? 'ring-2 ring-blue-300' : ''">
        <i class="fas fa-plus w-6 text-center text-lg"></i>
        <span x-show="sidebarOpen" class="font-semibold">Kelas</span>
        <i x-show="sidebarOpen" class="fas fa-chevron-down text-[10px] ml-auto transition-transform"
           :class="menuOpen ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="menuOpen"
         x-cloak
         @click.outside="menuOpen = false"
         class="absolute z-50 bg-white rounded-xl shadow-lg border border-gray-100 py-1 overflow-hidden"
         :class="sidebarOpen ? 'left-0 right-0 top-full mt-2' : 'left-full top-0 ml-2 w-52'">
        <button type="button"
                @click="modal = 'create'; menuOpen = false"
                class="flex w-full items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition text-left">
            <i class="fas fa-chalkboard-teacher w-5 text-center text-blue-600"></i>
            <span class="font-semibold">Buat Kelas Baru</span>
        </button>
    </div>

    @if(session('class_created'))
    @php $createdClass = session('class_created'); @endphp
    <div class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs" x-show="sidebarOpen">
        <p class="font-bold text-emerald-800">Kelas dibuat</p>
        <p class="text-emerald-700 mt-1">{{ $createdClass['name'] }} · {{ $createdClass['jurusan'] ?? '' }}</p>
        <p class="text-[10px] text-emerald-600">{{ $createdClass['academic_year'] ?? '' }} · {{ $createdClass['semester'] ?? '' }}</p>
        <p class="mt-2 font-mono font-bold text-lg tracking-widest text-emerald-900">{{ $createdClass['join_code'] }}</p>
        <p class="text-[10px] text-emerald-600 mt-1">Bagikan kode ini ke mahasiswa.</p>
    </div>
    @endif

    <template x-teleport="body">
    <div x-show="modal === 'create'"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-6 bg-slate-900/60"
         @click.self="modal = null">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-6xl max-h-[92vh] flex flex-col p-8 border border-slate-200" @click.stop>
            <div class="flex items-center justify-between mb-5 border-b pb-4 shrink-0">
                <h3 class="font-bold text-gray-800 text-base uppercase">Buat Kelas Baru</h3>
                <button type="button" @click="modal = null" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('dosen.classes.store') }}" class="flex flex-col flex-1 min-h-0"
                  onsubmit="var j=this.querySelector('[name=jurusan]');if(j)j.disabled=false;">
                @csrf

                @if($errors->any())
                <div class="rounded bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-xs mb-4 shrink-0">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-x-8 gap-y-4 flex-1 min-h-0 overflow-y-auto pr-2">
                    <div class="space-y-4">
                        @include('partials.fakultas-jurusan-alpine', [
                            'fakultasValue' => old('fakultas'),
                            'jurusanValue' => old('jurusan'),
                            'wrapperClass' => 'grid grid-cols-2 gap-3',
                        ])

                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Kelas</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: RPL Semester 4 — Kelas A"
                                   class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none @error('name') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Mata Kuliah</label>
                            <input type="text" name="course_name" value="{{ old('course_name') }}" required placeholder="Contoh: Pemrograman Web"
                                   class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none @error('course_name') border-red-400 @enderror">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tahun Ajaran</label>
                                <input type="text" name="academic_year" value="{{ old('academic_year', $defaultAcademicYear) }}" required
                                       placeholder="2025/2026"
                                       class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none @error('academic_year') border-red-400 @enderror">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Semester</label>
                                <select name="semester" required
                                        class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none bg-white @error('semester') border-red-400 @enderror">
                                    <option value="" disabled {{ old('semester') ? '' : 'selected' }}>Pilih</option>
                                    @foreach (['Ganjil', 'Genap', 'Pendek'] as $sem)
                                    <option value="{{ $sem }}" @selected(old('semester') === $sem)>{{ $sem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi Kelas</label>
                            <textarea name="description" rows="4" placeholder="Informasi singkat tentang kelas (opsional)"
                                      class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kapasitas Maks.</label>
                                <input type="number" name="max_members" value="{{ old('max_members') }}" min="1" max="500"
                                       placeholder="Opsional"
                                       class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none @error('max_members') border-red-400 @enderror">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kode Kelas</label>
                                <input type="text" name="custom_join_code" value="{{ old('custom_join_code') }}" maxlength="12"
                                       placeholder="Auto jika kosong"
                                       class="w-full rounded border border-gray-200 px-3 py-2 text-sm uppercase tracking-widest focus:border-blue-400 focus:outline-none @error('custom_join_code') border-red-400 @enderror">
                                <p class="text-[10px] text-gray-400 mt-1">4-12 karakter, huruf & angka.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Tipe Akses Kelas</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer hover:bg-gray-50 h-full {{ old('visibility', 'public') === 'public' ? 'border-blue-400 bg-blue-50/50' : 'border-gray-200' }}">
                                    <input type="radio" name="visibility" value="public" class="mt-0.5 shrink-0" @checked(old('visibility', 'public') === 'public')>
                                    <span>
                                        <span class="text-sm font-bold text-gray-800">Public Room</span>
                                        <span class="block text-[10px] text-gray-500 mt-0.5">Mahasiswa dapat gabung dengan kode kelas.</span>
                                    </span>
                                </label>
                                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer hover:bg-gray-50 h-full {{ old('visibility') === 'closed' ? 'border-blue-400 bg-blue-50/50' : 'border-gray-200' }}">
                                    <input type="radio" name="visibility" value="closed" class="mt-0.5 shrink-0" @checked(old('visibility') === 'closed')>
                                    <span>
                                        <span class="text-sm font-bold text-gray-800">Closed Room</span>
                                        <span class="block text-[10px] text-gray-500 mt-0.5">Hanya peserta yang diundang atau disetujui dosen.</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        @include('partials.class-invite-search', [
                            'people' => $lecturersForInvite,
                            'fieldName' => 'invite_lecturer_emails[]',
                            'label' => 'Undang Dosen',
                            'hint' => 'Opsional — cari dosen yang sudah punya akun DELPRO.',
                            'placeholder' => 'Nama atau email dosen...',
                            'oldValues' => old('invite_lecturer_emails', []),
                        ])

                        @include('partials.class-invite-search', [
                            'people' => $studentsForInvite,
                            'fieldName' => 'invite_student_emails[]',
                            'label' => 'Undang Mahasiswa',
                            'hint' => 'Opsional — disarankan untuk Closed Room.',
                            'placeholder' => 'Nama, email, atau NIM...',
                            'oldValues' => old('invite_student_emails', []),
                        ])
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-gray-100 shrink-0">
                    <button type="button" @click="modal = null"
                            class="px-4 py-2 rounded text-xs font-bold text-gray-600 border border-gray-200 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded text-xs font-bold text-white bg-blue-600 hover:bg-blue-700">
                        Buat Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
