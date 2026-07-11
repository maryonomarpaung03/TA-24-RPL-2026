{{--
    Bilah filter server-side yang dipakai ulang di banyak halaman.

    Parameter:
    - $action  : URL tujuan form (GET)
    - $search  : ['name' => 'q', 'value' => '...', 'placeholder' => '...'] (opsional)
    - $filters : list select => ['name', 'label', 'value', 'options' => [nilai => label]]
    - $summary : teks ringkasan hasil, mis. "Menampilkan 5 dari 18 proyek" (opsional)
    - $extraAction : tombol pintasan (link) di kiri "Terapkan" => ['url', 'label', 'icon' (opsional)]
    - $extraButton : tombol aksi Alpine di kanan "Terapkan" => ['click', 'label', 'icon' (opsional)]
--}}
@php
    $filters = $filters ?? [];
    $search = $search ?? null;
    $extraAction = $extraAction ?? null;
    $hasActive = collect($filters)->contains(fn ($f) => ($f['value'] ?? '') !== '')
        || ($search && ($search['value'] ?? '') !== '');
@endphp

<form method="GET" action="{{ $action }}"
      class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 mb-6">
    <div class="flex flex-wrap items-end gap-3 w-full">
        @if($search)
        <div class="flex-1 min-w-[220px]">
            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Cari</label>
            <div class="flex items-center bg-gray-50 rounded-full px-5 py-2 border border-transparent focus-within:border-blue-300 transition">
                <i class="fas fa-search text-gray-400 mr-3 text-xs"></i>
                <input type="text"
                       name="{{ $search['name'] }}"
                       value="{{ $search['value'] ?? '' }}"
                       placeholder="{{ $search['placeholder'] ?? 'Cari...' }}"
                       class="bg-transparent w-full outline-none text-sm py-0.5">
            </div>
        </div>
        @endif

        @foreach($filters as $filter)
        <div class="flex-1 min-w-[160px]">
            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">{{ $filter['label'] }}</label>
            <select name="{{ $filter['name'] }}"
                    onchange="this.form.submit()"
                    class="w-full rounded-full border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none focus:border-blue-300 transition">
                <option value="">Semua</option>
                @foreach($filter['options'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($filter['value'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endforeach

        <div class="flex items-center gap-2 ml-auto shrink-0">
            @if($extraAction)
            <a href="{{ $extraAction['url'] }}"
               class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-600 hover:bg-blue-50 transition">
                @isset($extraAction['icon'])<i class="{{ $extraAction['icon'] }}"></i>@endisset
                {{ $extraAction['label'] }}
            </a>
            @endif
            <button type="submit"
                    class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition">
                Terapkan
            </button>
            @isset($extraButton)
            <button type="button" @click="{{ $extraButton['click'] }}"
                    class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-600 hover:bg-blue-50 transition">
                @isset($extraButton['icon'])<i class="{{ $extraButton['icon'] }}"></i>@endisset
                {{ $extraButton['label'] }}
            </button>
            @endisset
            @if($hasActive)
            <a href="{{ $action }}"
               class="rounded-full border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                Reset
            </a>
            @endif
        </div>
    </div>

    @isset($summary)
    <p class="text-xs text-gray-400 mt-3">{{ $summary }}</p>
    @endisset
</form>
