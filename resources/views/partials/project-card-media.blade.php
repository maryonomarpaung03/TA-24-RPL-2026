@php
    $variant = $variant ?? 'card';
    $previewUrl = $previewUrl ?? null;
    $attachmentUrl = $attachmentUrl ?? null;
    $attachmentKind = $attachmentKind ?? null;
    $hasMedia = $hasMedia ?? false;
    $projectName = $projectName ?? 'Proyek';
    $isFeatured = $variant === 'featured';
    $heightClass = $isFeatured ? 'h-48 sm:h-52' : 'h-36';
@endphp

<div class="{{ $isFeatured ? 'relative' : '' }}">
    @if($isFeatured)
    <div class="bg-blue-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase inline-block mb-3">Featured Project</div>
    @endif

    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-slate-100 {{ $heightClass }}">
        @if($previewUrl)
            <img src="{{ $previewUrl }}" alt="Pratinjau {{ $projectName }}"
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>
        @elseif($hasMedia && $attachmentUrl)
            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener"
               class="flex h-full flex-col items-center justify-center gap-3 px-4 text-center transition hover:bg-slate-50/80">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    @if($attachmentKind === 'pdf')
                        <i class="fas fa-file-pdf text-2xl text-red-500"></i>
                    @else
                        <i class="fas fa-file-alt text-2xl text-blue-500"></i>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">Lampiran tersedia</p>
                    <p class="mt-1 text-[11px] text-slate-500">
                        {{ $attachmentKind === 'pdf' ? 'Dokumen PDF' : 'Buka file lampiran' }}
                    </p>
                </div>
            </a>
        @else
            <div class="flex h-full flex-col items-center justify-center gap-2 px-6 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/90 shadow-sm ring-1 ring-slate-200/80">
                    <i class="fas fa-folder-open text-lg text-slate-300"></i>
                </div>
                <p class="text-sm font-semibold text-slate-500">Belum ada lampiran</p>
                <p class="text-[11px] leading-relaxed text-slate-400">
                    Unggah gambar atau dokumen saat membuat atau mengedit proyek.
                </p>
            </div>
        @endif
    </div>
</div>
