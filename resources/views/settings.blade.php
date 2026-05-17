@extends('layouts.app')

@section('title', 'Settings - DELPRO')

@push('head')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
<div class="flex-1 p-6 overflow-y-auto" x-data="{ editMode: @js($editMode) }">
    <div class="w-full space-y-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Settings</p>
                    <h2 class="text-3xl font-bold text-slate-900">Profile</h2>
                    <p class="mt-2 text-sm text-slate-500">Manage your account information.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="inline-flex rounded-full bg-slate-100 p-1 border border-slate-200">
                        <a href="{{ route('settings') }}"
                           :class="editMode ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-blue-700 shadow-sm'"
                           class="rounded-full px-4 py-2 text-xs font-bold transition text-center">
                            View
                        </a>
                        <a href="{{ route('settings', ['edit' => 1]) }}"
                           :class="editMode ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                           class="rounded-full px-4 py-2 text-xs font-bold transition text-center">
                            Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data"
              class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm space-y-8">
            @csrf
            @method('PUT')

            <div class="flex flex-col lg:flex-row lg:items-start gap-6 pb-6 border-b border-slate-100">
                <div class="relative shrink-0">
                    @if($profile['photo_url'])
                    <img src="{{ $profile['photo_url'] }}" alt="Profile photo"
                         class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-md ring-2 ring-blue-100">
                    @else
                    <div class="h-24 w-24 rounded-full bg-blue-600 text-white grid place-items-center text-2xl font-bold shadow-md ring-2 ring-blue-100">
                        {{ $profile['initials'] }}
                    </div>
                    @endif
                </div>
                <div class="text-left flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-slate-900">{{ $profile['full_name'] }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $profile['email'] }}</p>
                    <span class="inline-flex mt-3 rounded-full bg-blue-50 border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-700">
                        {{ $profile['role_label'] }}
                    </span>
                </div>
                <div x-show="editMode" x-cloak class="w-full sm:w-auto">
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Profile photo</label>
                    <input type="file" name="profile_photo" accept="image/*"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-[11px] text-slate-400">JPG, PNG. Max 2 MB.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="text-xs font-semibold text-slate-500">Full name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $profile['full_name']) }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Username</label>
                    <input type="text" value="{{ $profile['username'] }}" disabled
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    <p class="mt-1 text-[11px] text-slate-400">Username cannot be changed.</p>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Email</label>
                    <input type="email" name="email" value="{{ old('email', $profile['email']) }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                @if($profile['is_lecturer'])
                <div>
                    <label class="text-xs font-semibold text-slate-500">NIDN</label>
                    <input type="text" name="nidn" value="{{ old('nidn', $profile['nidn']) }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>
                @else
                <div>
                    <label class="text-xs font-semibold text-slate-500">Student ID (NIM)</label>
                    <input type="text" name="nim" value="{{ old('nim', $profile['nim']) }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Batch year</label>
                    <input type="number" name="batch_year" value="{{ old('batch_year', $profile['batch_year']) }}"
                           min="2000" max="{{ date('Y') + 1 }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Faculty</label>
                    <select name="fakultas" id="settings-fakultas" @disabled(!$editMode)
                            class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                        <option value="" disabled>Pilih Fakultas</option>
                        @foreach(array_keys($facultyPrograms) as $fakultasName)
                        <option value="{{ $fakultasName }}" @selected(old('fakultas', $profile['fakultas']) === $fakultasName)>{{ $fakultasName }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Study program</label>
                    <select name="jurusan" id="settings-jurusan" @disabled(!$editMode)
                            class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                        <option value="" disabled selected>Pilih Jurusan</option>
                    </select>
                </div>
                @endif

                <div>
                    <label class="text-xs font-semibold text-slate-500">Phone / WhatsApp</label>
                    <input type="tel" name="phone" value="{{ old('phone', $profile['phone']) }}"
                           @disabled(!$editMode)
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500">Gender</label>
                    <select name="gender" @disabled(!$editMode)
                            class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                        <option value="Laki-laki" @selected(old('gender', $profile['gender']) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('gender', $profile['gender']) === 'Perempuan')>Perempuan</option>
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label class="text-xs font-semibold text-slate-500">Place & date of birth</label>
                    <input type="text" name="birth_place_date" value="{{ old('birth_place_date', $profile['birth_place_date']) }}"
                           @disabled(!$editMode)
                           placeholder="e.g. Jakarta, 1 January 2004"
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label class="text-xs font-semibold text-slate-500">Address</label>
                    <textarea name="address" rows="3" @disabled(!$editMode)
                              class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400 disabled:bg-slate-50 disabled:text-slate-600">{{ old('address', $profile['address']) }}</textarea>
                </div>
            </div>

            <div x-show="editMode" x-cloak class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('settings') }}"
                   class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i>
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@if(!$profile['is_lecturer'])
@push('scripts')
<script>
(function () {
    var programsByFaculty = @json($facultyPrograms);
    var currentFakultas = @json(old('fakultas', $profile['fakultas']));
    var currentJurusan = @json(old('jurusan', $profile['jurusan']));
    var fakultasSelect = document.getElementById('settings-fakultas');
    var jurusanSelect = document.getElementById('settings-jurusan');
    if (!fakultasSelect || !jurusanSelect) return;

    function fillJurusan(fakultas, selectedJurusan) {
        jurusanSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.selected = !selectedJurusan;
        placeholder.textContent = fakultas ? 'Pilih Jurusan' : 'Select faculty first';
        jurusanSelect.appendChild(placeholder);

        if (!fakultas || !programsByFaculty[fakultas]) {
            jurusanSelect.disabled = true;
            return;
        }

        (programsByFaculty[fakultas] || []).forEach(function (program) {
            var option = document.createElement('option');
            option.value = program;
            option.textContent = program;
            if (selectedJurusan && selectedJurusan === program) {
                option.selected = true;
                placeholder.selected = false;
            }
            jurusanSelect.appendChild(option);
        });

        jurusanSelect.disabled = @json(!$editMode);
    }

    fakultasSelect.addEventListener('change', function () {
        fillJurusan(fakultasSelect.value, null);
    });

    if (currentFakultas) {
        fakultasSelect.value = currentFakultas;
        fillJurusan(currentFakultas, currentJurusan);
    }

    fakultasSelect.closest('form').addEventListener('submit', function () {
        if (fakultasSelect.value) {
            jurusanSelect.disabled = false;
        }
    });
})();
</script>
@endpush
@endif
