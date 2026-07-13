@extends('layouts.app')

@section('title', 'Kelola Proyek - DELPRO')

@section('content')
<div class="w-full space-y-6">

    <a href="{{ route('dosen.proyek-mahasiswa') }}" class="text-blue-500 text-xs font-bold hover:underline mb-4 inline-block">
        &larr; Kembali ke Proyek Mahasiswa
    </a>

    @include('partials.flash-messages')

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4 border-b pb-4">
            <div>
                <p class="text-[10px] font-bold uppercase mb-1 {{ $project['status'] === 'completed' ? 'text-emerald-600' : 'text-blue-600' }}">
                    {{ $project['status_label'] }}
                </p>
                <h1 class="text-2xl font-bold text-gray-900">{{ $project['name'] }}</h1>
                <p class="text-sm text-gray-500 mt-2">
                    {{ $project['group_name'] ?? '-' }} &middot; {{ $project['course_name'] ?? '-' }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Project Manager: {{ $project['creator_name'] }} ({{ $project['creator_email'] }})
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Masalah Utama</p>
                <p class="text-sm text-gray-700">{{ $project['masalah'] ?: '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Periode</p>
                <p class="text-sm text-gray-700">{{ $project['start_date'] ?? '-' }} &mdash; {{ $project['end_date'] ?? '-' }}</p>
            </div>
        </div>

        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $project['deskripsi'] ?: '-' }}</p>
        </div>

        @if(!empty($project['attachment_url']))
        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Lampiran</p>
            <a href="{{ $project['attachment_url'] }}" target="_blank" rel="noopener"
               class="text-blue-500 text-xs font-bold hover:underline">Buka lampiran</a>
        </div>
        @endif

        @if(count($project['members']) > 0)
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Anggota Tim</p>
            <ul class="space-y-2">
                @foreach($project['members'] as $member)
                <li class="text-sm text-gray-700 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ $member['initials'] }}</span>
                    {{ $member['name'] }} <span class="text-gray-400">({{ $member['email'] }})</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    @php
        $stageTone = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
            'slate' => 'border-slate-200 bg-slate-100 text-slate-600',
            'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
        ];
        $pendingReopen = $reopen_requests->where('status', 'pending');
    @endphp

    @if($pendingReopen->isNotEmpty())
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-amber-200">
        <h2 class="text-sm font-bold text-gray-800 uppercase mb-1">Permintaan Perbaikan Tahapan</h2>
        <p class="text-xs text-gray-500 mb-5">
            Tim meminta membuka kembali tahapan yang sudah difinalisasi. Bila disetujui, tahapan tersebut
            dapat diedit lagi dan harus difinalisasi ulang oleh tim.
        </p>

        <div class="space-y-4">
            @foreach($pendingReopen as $req)
            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900">
                            {{ \App\Services\StageProgressService::label($req->stage) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Diajukan {{ $req->requester?->full_name ?? $req->requester?->name ?? 'anggota tim' }}
                            &middot; {{ $req->created_at?->translatedFormat('d M Y, H:i') }}
                        </p>
                        <p class="text-sm text-gray-700 mt-3 whitespace-pre-line">{{ $req->reason }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-end gap-2">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">
                            Catatan untuk tim (opsional)
                        </label>
                        <input type="text" form="reopen-{{ $req->id }}" name="lecturer_note" maxlength="1000"
                               placeholder="Mis. perbaiki sub-masalah nomor 3"
                               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500">
                    </div>

                    <form id="reopen-{{ $req->id }}" method="POST"
                          action="{{ route('dosen.stage.reopen.approve', [$project['id'], $req->id]) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                            <i class="fas fa-check mr-1"></i>Setujui
                        </button>
                    </form>

                    <form method="POST" action="{{ route('dosen.stage.reopen.reject', [$project['id'], $req->id]) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-xmark mr-1"></i>Tolak
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-sm font-bold text-gray-800 uppercase mb-1">Progres Tahapan Computational Thinking</h2>
        <p class="text-xs text-gray-500 mb-5">
            Tahapan dikerjakan berurutan. Tahapan bertanda <span class="font-semibold text-amber-600">Dilewati</span>
            difinalisasi otomatis saat tim melompat ke tahapan berikutnya tanpa menyelesaikannya.
        </p>

        <div class="space-y-3">
            @foreach($stage_overview['stages'] as $stage)
            <div class="rounded-2xl border {{ $stage['state'] === 'done' ? 'border-gray-200' : 'border-dashed border-gray-200 bg-gray-50/50' }} p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                     {{ $stage['state'] === 'done' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-500' }}">
                            {{ $stage['number'] }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ $stage['label'] }}</p>
                            <p class="text-xs text-gray-500">
                                @if($stage['state'] === 'done')
                                    Difinalisasi
                                    @if($stage['finalized_at']) {{ $stage['finalized_at']->translatedFormat('d M Y, H:i') }} @endif
                                    @if($stage['finalized_by']) oleh {{ $stage['finalized_by'] }} @endif
                                @elseif($stage['state'] === 'current')
                                    Sedang dikerjakan tim.
                                @else
                                    Belum dibuka.
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($stage['badge'])
                    <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $stageTone[$stage['badge']['tone']] ?? $stageTone['slate'] }}">
                        {{ $stage['badge']['label'] }}
                    </span>
                    @endif
                </div>

                @if($stage['state'] === 'done' && $stage['summary_items'])
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 border-t pt-4">
                    @foreach($stage['summary_items'] as $item)
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $item['label'] }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $item['value'] }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-sm font-bold text-gray-800 uppercase mb-1">Aksi Dosen</h2>
        <p class="text-xs text-gray-500 mb-5">Pantau dan tinjau hasil kerja mahasiswa pada proyek ini.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('dosen.problem-review', $project['id']) }}"
               class="rounded-2xl border p-4 hover:border-blue-300 hover:bg-blue-50/50 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    @if($project['pending_problem_review'])
                    <span class="text-[10px] font-bold uppercase text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Perlu review</span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-900 mt-3 group-hover:text-blue-700">Identifikasi Masalah</h3>
                <p class="text-xs text-gray-500 mt-1">Review masalah utama hasil voting tim mahasiswa.</p>
            </a>

            <a href="{{ route('dosen.dekomposisi', $project['id']) }}"
               class="rounded-2xl border p-4 hover:border-purple-300 hover:bg-purple-50/50 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                </div>
                <h3 class="font-bold text-gray-900 mt-3 group-hover:text-purple-700">Dekomposisi Masalah</h3>
                <p class="text-xs text-gray-500 mt-1">Lihat diagram dekomposisi dan history pembuatan dari tim mahasiswa.</p>
            </a>

            <a href="{{ route('dosen.penyusunan', $project['id']) }}"
               class="rounded-2xl border p-4 hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-list-check"></i>
                    </div>
                </div>
                <h3 class="font-bold text-gray-900 mt-3 group-hover:text-emerald-700">Project Planning</h3>
                <p class="text-xs text-gray-500 mt-1">Lihat rencana dan daftar tugas yang disusun tim mahasiswa.</p>
            </a>

            <a href="{{ route('dosen.pelaksanaan', $project['id']) }}"
               class="rounded-2xl border p-4 hover:border-orange-300 hover:bg-orange-50/50 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-diagram-project"></i>
                    </div>
                </div>
                <h3 class="font-bold text-gray-900 mt-3 group-hover:text-orange-700">Execution &amp; Evaluation</h3>
                <p class="text-xs text-gray-500 mt-1">Pantau papan kanban, progres, dan kontribusi tiap mahasiswa.</p>
            </a>

            <a href="{{ route('dosen.penilaian', $project['id']) }}"
               class="rounded-2xl border p-4 hover:border-purple-300 hover:bg-purple-50/50 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    @if($project['is_evaluated'])
                        <span class="text-[10px] font-bold text-emerald-600 uppercase">Sudah dinilai</span>
                    @elseif(! $project['tasks_finalized'])
                        <span class="text-[10px] font-bold text-red-600 uppercase">Belum final</span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-900 mt-3 group-hover:text-purple-700">Penilaian</h3>
                <p class="text-xs text-gray-500 mt-1">Beri nilai kelompok dan individu, lengkap dengan catatan untuk mahasiswa.</p>
            </a>
        </div>
    </div>
</div>
@endsection
