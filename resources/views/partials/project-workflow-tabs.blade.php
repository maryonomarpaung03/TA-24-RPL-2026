@php
    $pid = $selected_project['id'];
    $wfChat = Request::routeIs('project-chat');
    $overview = $stage_overview ?? null;

    $tabDone = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100 transition';
    $tabActive = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm';
    $tabIdle = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition';
    $tabLocked = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400 cursor-not-allowed';

    $badgeTone = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'slate' => 'bg-slate-200 text-slate-600',
        'blue' => 'bg-blue-100 text-blue-700',
    ];
@endphp

<div class="border-b border-slate-200 bg-slate-50/90 px-4 py-3 z-30"
     x-data="{
        confirmStage: null,
        open(payload) { this.confirmStage = payload; },
     }">
    <div class="flex flex-col gap-3 max-w-[100vw]">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold shrink-0">Selected project</span>
            <span class="text-sm font-semibold text-slate-900 truncate">{{ $selected_project['name'] }}</span>
        </div>

        @if(session('stage_locked'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800">
                <i class="fas fa-lock mr-1.5"></i>{{ session('stage_locked') }}
            </div>
        @endif

        <nav class="flex gap-2 overflow-x-auto pb-0.5" aria-label="Alur kerja proyek">
            @if($overview)
                @foreach($overview['stages'] as $stage)
                    @php
                        $isActiveTab = ($active_stage ?? null) === $stage['key'];
                        $current = $overview['stages'][$overview['current_index']];
                    @endphp

                    @if($stage['state'] === 'locked')
                        {{-- Lebih dari satu langkah di depan: tidak bisa diklik sama sekali. --}}
                        <span class="{{ $tabLocked }}"
                              title="Selesaikan tahapan {{ $current['label'] }} terlebih dahulu.">
                            <i class="fas fa-lock text-[11px]"></i>
                            {{ $stage['label'] }}
                        </span>

                    @elseif($stage['state'] === 'next')
                        {{-- Tepat satu langkah di depan: boleh diklik, tapi harus konfirmasi. --}}
                        <button type="button"
                                class="{{ $tabIdle }}"
                                @click="open({
                                    target: '{{ $stage['key'] }}',
                                    targetLabel: @js($stage['label']),
                                    currentLabel: @js($current['label']),
                                    warnings: @js($current['warnings']),
                                })">
                            <i class="fas {{ $stage['icon'] }} text-[11px]"></i>
                            {{ $stage['label'] }}
                            <i class="fas fa-circle-exclamation text-[10px] text-amber-500"></i>
                        </button>

                    @else
                        <a href="{{ route($stage['route'], $pid) }}"
                           class="{{ $isActiveTab ? $tabActive : ($stage['state'] === 'done' ? $tabDone : $tabIdle) }}">
                            <i class="fas {{ $stage['state'] === 'done' ? 'fa-circle-check' : $stage['icon'] }} text-[11px]"></i>
                            {{ $stage['label'] }}
                            @if($stage['badge'] && $stage['state'] === 'done')
                                <span class="rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase {{ $badgeTone[$stage['badge']['tone']] ?? $badgeTone['slate'] }}">
                                    {{ $stage['badge']['label'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            @endif

            <a href="{{ route('project-chat', $pid) }}" class="{{ $wfChat ? $tabActive : $tabIdle }}">
                <i class="fas fa-comments text-[11px]"></i>
                Project Chat
            </a>
        </nav>
    </div>

    {{-- Konfirmasi lompat tahap: tahap berjalan difinalisasi otomatis bila tim setuju. --}}
    <div x-show="confirmStage" x-cloak
         class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="confirmStage = null">

        <div @click.outside="confirmStage = null"
             class="w-full max-w-lg rounded-[2rem] bg-white p-8 shadow-2xl">

            <div class="mb-5 flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                    <i class="fas fa-triangle-exclamation text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">
                        Tahapan <span x-text="confirmStage?.currentLabel"></span> belum diselesaikan
                    </h2>
                    <p class="mt-1.5 text-sm text-gray-600">
                        Apakah Anda ingin melanjutkan ke tahapan
                        <span class="font-semibold text-gray-900" x-text="confirmStage?.targetLabel"></span>?
                        Jika ya, tahapan <span class="font-semibold text-gray-900" x-text="confirmStage?.currentLabel"></span>
                        akan otomatis difinalisasi, dosen akan diberi tahu, dan tahapan tersebut tidak dapat diubah lagi
                        kecuali Anda mengajukan perbaikan.
                    </p>
                </div>
            </div>

            <template x-if="confirmStage?.warnings?.length">
                <div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-blue-700">
                        Perhatian sebelum melanjutkan
                    </p>
                    <ul class="space-y-1.5">
                        <template x-for="warning in confirmStage.warnings" :key="warning">
                            <li class="flex items-start gap-2 text-xs text-blue-800">
                                <i class="fas fa-circle-dot mt-0.5 text-[9px]"></i>
                                <span x-text="warning"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <form method="POST" action="{{ route('stages.advance', $pid) }}" class="flex items-center justify-end gap-3">
                @csrf
                <input type="hidden" name="target" :value="confirmStage?.target">

                <button type="button" @click="confirmStage = null"
                        class="rounded-xl bg-gray-200 px-6 py-3 font-semibold text-gray-600 hover:bg-gray-300">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700">
                    <i class="fas fa-forward mr-1"></i>Ya, finalisasi &amp; lanjut
                </button>
            </form>
        </div>
    </div>
</div>
