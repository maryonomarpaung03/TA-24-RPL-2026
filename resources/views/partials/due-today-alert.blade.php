{{--
    Banner peringatan tugas yang jatuh tempo hari ini dan belum selesai.

    Parameter:
    - $dueTodayTasks : semua tugas jatuh tempo hari ini (list dengan 'judul', 'pj', 'assigned_to')
    - $dueTodayMine  : bagian tugas di atas yang menjadi tanggung jawab user login
--}}
@php
    $dueTodayTasks = $dueTodayTasks ?? [];
    $dueTodayMine = $dueTodayMine ?? [];
    $mineCount = count($dueTodayMine);
    $othersCount = count($dueTodayTasks) - $mineCount;
@endphp

@if(count($dueTodayTasks) > 0)
<div x-data="{ show: true }" x-show="show" x-transition
     class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
    <div class="flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white">
            <i class="fas fa-bell"></i>
        </div>

        <div class="flex-1">
            <h3 class="text-sm font-bold text-rose-700">
                @if($mineCount > 0)
                    {{ $mineCount }} tugas kamu harus diselesaikan hari ini
                    ({{ now()->translatedFormat('d F Y') }})
                @else
                    {{ count($dueTodayTasks) }} tugas tim jatuh tempo hari ini
                    ({{ now()->translatedFormat('d F Y') }})
                @endif
            </h3>

            <ul class="mt-2 space-y-1">
                @foreach(($mineCount > 0 ? $dueTodayMine : $dueTodayTasks) as $task)
                <li class="flex flex-wrap items-center gap-2 text-xs text-rose-700">
                    <i class="fas fa-circle text-[5px]"></i>
                    <span class="font-bold">{{ $task['judul'] }}</span>
                    <span class="text-rose-500">— {{ $task['pj'] }}</span>
                    <span class="rounded-full bg-white px-2 py-0.5 font-bold text-rose-600">
                        {{ $task['status']['label'] }}
                    </span>
                </li>
                @endforeach
            </ul>

            @if($mineCount > 0 && $othersCount > 0)
            <p class="mt-2 text-[11px] text-rose-500">
                Ditambah {{ $othersCount }} tugas anggota lain yang juga jatuh tempo hari ini.
            </p>
            @endif
        </div>

        <button type="button" @click="show = false"
                class="shrink-0 rounded-full p-2 text-rose-400 hover:bg-rose-100 hover:text-rose-600 transition"
                aria-label="Tutup peringatan">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif
