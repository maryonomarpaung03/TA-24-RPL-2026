@php
    $facultyPrograms = $facultyPrograms ?? config('faculties.programs', []);
    $fakultasValue = $fakultasValue ?? old('fakultas', '');
    $jurusanValue = $jurusanValue ?? old('jurusan', '');
    $selectClass = $selectClass ?? 'w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none bg-white';
@endphp

<div x-data="{
    fakultas: @js($fakultasValue),
    jurusan: @js($jurusanValue),
    programs: @js($facultyPrograms),
    jurusanList() {
        return this.fakultas && this.programs[this.fakultas] ? this.programs[this.fakultas] : [];
    }
}" class="{{ $wrapperClass ?? 'space-y-4' }}">
    <div>
        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Fakultas</label>
        <select name="fakultas" x-model="fakultas" @change="jurusan = ''" required class="{{ $selectClass }}">
            <option value="" disabled>Pilih Fakultas</option>
            @foreach (array_keys($facultyPrograms) as $fakultasName)
                <option value="{{ $fakultasName }}">{{ $fakultasName }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jurusan</label>
        <select name="jurusan" x-model="jurusan" x-ref="jurusanSelect"
                :disabled="!fakultas"
                required
                class="{{ $selectClass }} disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400">
            <option value="" disabled>Pilih Jurusan</option>
            <template x-for="item in jurusanList()" :key="item">
                <option :value="item" x-text="item"></option>
            </template>
        </select>
        <p x-show="!fakultas" class="text-[10px] text-gray-400 mt-1">Pilih fakultas terlebih dahulu.</p>
    </div>
</div>
