@php
    $facultyPrograms = $facultyPrograms ?? config('faculties.programs', []);
    $prefix = $prefix ?? 'fj';
    $fakultasId = $prefix . '_fakultas';
    $jurusanId = $prefix . '_jurusan';
    $selectClass = $selectClass ?? 'w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none bg-white';
    $fakultasValue = $fakultasValue ?? old('fakultas');
    $jurusanValue = $jurusanValue ?? old('jurusan');
    $isRequired = $isRequired ?? true;
@endphp

<div>
    <label for="{{ $fakultasId }}" class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Fakultas</label>
    <select id="{{ $fakultasId }}" name="fakultas"
            @if($isRequired) required @endif
            class="{{ $selectClass }}">
        <option value="" disabled {{ $fakultasValue ? '' : 'selected' }}>Pilih Fakultas</option>
        @foreach (array_keys($facultyPrograms) as $fakultasName)
            <option value="{{ $fakultasName }}" @selected($fakultasValue === $fakultasName)>{{ $fakultasName }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="{{ $jurusanId }}" class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jurusan</label>
    <select id="{{ $jurusanId }}" name="jurusan"
            @if($isRequired) required @endif
            disabled
            class="{{ $selectClass }} disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400">
        <option value="" disabled selected>Pilih Jurusan</option>
    </select>
</div>
