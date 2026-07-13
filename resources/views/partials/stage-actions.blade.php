@php
    /** Bar aksi tahapan CT: tombol finalisasi, ringkasan tahap terkunci, dan
        pengajuan perbaikan. Dirender dari layout untuk semua halaman tahapan,
        sehingga kelima view tahap tidak perlu tahu soal waterfall. */
    $pid = $selected_project['id'];
    $stage = collect($stage_overview['stages'])->firstWhere('key', $active_stage);

    // Tahap Assessment tidak punya tombol "Finalisasi Tahap": mengirim laporan akhir
    // lewat Submit Finalisasi Proyek itulah yang menutup tahap ini.
    $isAssessment = $active_stage === \App\Services\StageProgressService::ASSESSMENT;
    $projectLocked = \App\Support\ProjectAccess::isFinalized($selected_project['status'] ?? null);
    $showFinalProject = $isAssessment && ! $projectLocked;

    $toneClasses = [
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'slate' => 'border-slate-200 bg-slate-100 text-slate-600',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
    ];
@endphp

@if($stage)
<div class="border-b border-slate-200 bg-white px-4 py-3"
     x-data="{ finalizeOpen: false, reopenOpen: false, finalModal: false }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                {{ $stage['number'] }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-900">{{ $stage['label'] }}</p>
                @if($stage['state'] === 'done')
                    <p class="text-xs text-slate-500">
                        Difinalisasi
                        @if($stage['finalized_at']) {{ $stage['finalized_at']->translatedFormat('d M Y, H:i') }} @endif
                        @if($stage['finalized_by']) oleh {{ $stage['finalized_by'] }} @endif
                        &middot; tahapan ini hanya dapat dibaca.
                    </p>
                @else
                    <p class="text-xs text-slate-500">Tahapan sedang berjalan &middot; belum difinalisasi.</p>
                @endif
            </div>

            @if($stage['badge'])
                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $toneClasses[$stage['badge']['tone']] ?? $toneClasses['slate'] }}">
                    {{ $stage['badge']['label'] }}
                </span>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($stage['state'] === 'current' && ! $isAssessment)
                <button type="button" @click="finalizeOpen = true"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    <i class="fas fa-flag-checkered"></i>
                    Finalisasi Tahap
                </button>

            @elseif($stage['state'] === 'done')
                @if($stage['pending_reopen'])
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-700">
                        <i class="fas fa-hourglass-half"></i>
                        Menunggu persetujuan perbaikan dari dosen
                    </span>
                @elseif($stage['can_reopen'])
                    <button type="button" @click="reopenOpen = true"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-rotate-left"></i>
                        Ajukan Perbaikan
                    </button>
                @endif
            @endif

            @if($showFinalProject)
                <button type="button" @click="finalModal = true"
                        class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                    <i class="fas fa-paper-plane"></i>
                    Submit Finalisasi Proyek
                </button>
            @endif

            @if($isAssessment && $projectLocked)
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700">
                    <i class="fas fa-lock"></i>
                    {{ ($selected_project['status'] ?? null) === 'completed'
                        ? 'Proyek selesai & sudah dinilai'
                        : 'Finalisasi terkirim — menunggu penilaian dosen' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Ringkasan tahap: sama persis dengan yang dilihat dosen. --}}
    @if($stage['state'] === 'done' && $stage['summary_items'])
        <div class="mt-3 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
            @foreach($stage['summary_items'] as $item)
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if($isAssessment && $stage['state'] !== 'done')
        <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs text-slate-500">
            <i class="fas fa-circle-info mr-1"></i>
            Isi refleksi &amp; penilaian antar anggota, lalu kirim laporan akhir lewat
            <span class="font-semibold">Submit Finalisasi Proyek</span>. Nilai dari dosen baru dapat dilihat
            setelah laporan akhir dikirim.
        </p>
    @endif

    {{-- Konfirmasi finalisasi tahap (tidak berlaku untuk Assessment). --}}
    @unless($isAssessment)
    <div x-show="finalizeOpen" x-cloak
         class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="finalizeOpen = false">

        <div @click.outside="finalizeOpen = false" class="w-full max-w-lg rounded-[2rem] bg-white p-8 shadow-2xl">
            <div class="mb-5 flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <i class="fas fa-flag-checkered text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Finalisasi Tahap: {{ $stage['label'] }}</h2>
                    <p class="mt-1.5 text-sm text-gray-600">
                        Tahapan ini akan dikunci dan tidak dapat diubah lagi, dosen akan menerima notifikasi
                        beserta ringkasannya, dan tahapan berikutnya terbuka. Untuk mengubahnya kembali, Anda
                        harus mengajukan perbaikan dan menunggu persetujuan dosen.
                    </p>
                </div>
            </div>

            @if($stage['warnings'])
                <div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-blue-700">Perhatian</p>
                    <ul class="space-y-1.5">
                        @foreach($stage['warnings'] as $warning)
                            <li class="flex items-start gap-2 text-xs text-blue-800">
                                <i class="fas fa-circle-dot mt-0.5 text-[9px]"></i>
                                <span>{{ $warning }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs font-semibold text-blue-800">
                        Anda tetap dapat melanjutkan, tetapi ringkasan yang diterima dosen akan mencerminkan kondisi ini.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('stages.finalize', $pid) }}" class="flex items-center justify-end gap-3">
                @csrf
                <input type="hidden" name="stage" value="{{ $stage['key'] }}">

                <button type="button" @click="finalizeOpen = false"
                        class="rounded-xl bg-gray-200 px-6 py-3 font-semibold text-gray-600 hover:bg-gray-300">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700">
                    <i class="fas fa-check mr-1"></i>Ya, finalisasi
                </button>
            </form>
        </div>
    </div>
    @endunless

    {{-- Pengajuan perbaikan ke dosen --}}
    <div x-show="reopenOpen" x-cloak
         class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="reopenOpen = false">

        <div @click.outside="reopenOpen = false" class="w-full max-w-lg rounded-[2rem] bg-white p-8 shadow-2xl">
            <h2 class="text-xl font-bold text-gray-900">Ajukan Perbaikan: {{ $stage['label'] }}</h2>
            <p class="mt-1.5 mb-5 text-sm text-gray-600">
                Tahapan ini sudah difinalisasi. Jelaskan apa yang perlu diperbaiki — dosen akan menyetujui
                atau menolak permintaan ini. Bila disetujui, tahapan dibuka kembali dan Anda perlu
                memfinalisasinya ulang setelah selesai.
            </p>

            <form method="POST" action="{{ route('stages.reopen', $pid) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="stage" value="{{ $stage['key'] }}">

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">
                        Alasan perbaikan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" rows="4" required minlength="10" maxlength="1000"
                              placeholder="Contoh: dekomposisi kami melewatkan satu sub-masalah penting..."
                              class="w-full resize-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-blue-500"></textarea>
                    <p class="mt-1 text-[11px] text-gray-400">Minimal 10 karakter.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" @click="reopenOpen = false"
                            class="rounded-xl bg-gray-200 px-6 py-3 font-semibold text-gray-600 hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-1"></i>Kirim permintaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Form laporan akhir ke dosen; mengirimnya sekaligus menutup tahap ini. --}}
    @if($showFinalProject)
        @include('partials.finalisasi-modal')
    @endif
</div>
@endif
