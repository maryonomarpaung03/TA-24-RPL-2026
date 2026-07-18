@extends('layouts.app')

@section('title', 'Review Masalah Utama - PjBL')

@section('content')
<div class="w-full space-y-6">

    <a href="{{ route('dosen.proyek-mahasiswa.show', $project['id']) }}" class="text-blue-600 text-xs font-bold hover:underline mb-4 inline-block">
        &larr; Kembali ke detail proyek
    </a>

    @include('partials.flash-messages')

    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm mb-6">
        <p class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-2">Problem Identification</p>
        <h1 class="text-2xl font-bold text-slate-900">{{ $project['name'] }}</h1>
        <p class="text-sm text-slate-500 mt-2">{{ $project['group_name'] ?? '-' }} &middot; {{ $project['course_name'] ?? '-' }}</p>
        <p class="text-sm text-slate-600 mt-3">Project Manager: <strong>{{ $project['creator_name'] }}</strong></p>
    </div>

    @if($activeReview)
    <div class="grid grid-cols-1 xl:grid-cols-[1.9fr_1fr] gap-6 mb-6">
        <div class="bg-white rounded-3xl border border-amber-200 p-6 shadow-sm">
            <p class="text-xs font-bold text-amber-600 uppercase mb-3">Menunggu review Anda</p>
            <h2 class="text-xl font-bold text-slate-900">{{ $activeReview->title }}</h2>
            <p class="text-sm text-slate-600 mt-3 whitespace-pre-line">{{ $activeReview->description }}</p>
            <div class="flex flex-wrap gap-2 mt-4 text-xs">
                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $activeReview->category }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $activeReview->priority }}</span>
                @php
                    $attachmentLink = $activeReview->attachment_link;
                    $decodedAttachment = is_string($attachmentLink) ? json_decode($attachmentLink, true) : null;
                    if (is_array($decodedAttachment)) {
                        $attachmentLink = collect($decodedAttachment)
                            ->pluck('value')
                            ->filter(fn ($value) => is_string($value) && filter_var($value, FILTER_VALIDATE_URL))
                            ->first();
                    }
                @endphp
                @if($attachmentLink)
                <a href="{{ $attachmentLink }}" target="_blank" rel="noopener" class="rounded-full bg-blue-50 px-3 py-1 text-blue-700 hover:bg-blue-100 hover:underline">
                    <i class="fas fa-paperclip mr-1"></i>Buka lampiran
                </a>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-3">Diajukan oleh {{ $activeReview->author_name ?? 'Tim' }}</p>

            <form method="POST" action="{{ route('dosen.problem-review.submit', [$project['id'], $activeReview->id]) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-slate-500">Umpan balik / catatan untuk mahasiswa</label>
                    <textarea name="feedback" rows="4" class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="Wajib diisi jika menolak. Opsional jika menyetujui.">{{ old('feedback') }}</textarea>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="action" value="approve" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        <i class="fas fa-check"></i> Setujui
                    </button>
                    <button type="submit" name="action" value="reject" class="inline-flex items-center gap-2 rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition">
                        <i class="fas fa-times"></i> Tolak &amp; minta perbaikan
                    </button>
                </div>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-1">Hasil Voting</h3>
                <p class="text-xs text-slate-500 mb-4">{{ count($voters) }} / {{ $participantCount }} anggota sudah vote untuk ide ini</p>
                @if(count($voters) > 0)
                <ul class="space-y-3">
                    @foreach($voters as $voter)
                    <li class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 border border-slate-200">
                        <div class="h-9 w-9 shrink-0 rounded-full bg-blue-600 text-white grid place-items-center text-xs font-bold">{{ $voter['initials'] }}</div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $voter['name'] }}</p>
                            <p class="text-[11px] text-slate-500">Vote {{ $voter['voted_at'] }}</p>
                        </div>
                        <span class="ml-auto shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Vote</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-slate-400 text-center py-4">Belum ada anggota yang vote untuk ide ini.</p>
                @endif
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Diskusi Tim</h3>
                @if(count($comments) > 0)
                <ul class="space-y-3">
                    @foreach($comments as $comment)
                    <li class="rounded-2xl bg-slate-50 p-3 border border-slate-200">
                        <div class="flex items-center justify-between text-[11px] text-slate-500 gap-2">
                            <span class="font-semibold text-slate-700">{{ $comment['from'] }}</span>
                            <span class="shrink-0">{{ $comment['time'] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $comment['text'] }}</p>
                        @if(!empty($comment['replies']))
                        <ul class="mt-3 ml-3 space-y-2 border-l-2 border-slate-200 pl-3">
                            @foreach($comment['replies'] as $reply)
                            <li class="rounded-xl bg-white p-2.5 border border-slate-100">
                                <div class="flex items-center justify-between text-[10px] text-slate-500 gap-2">
                                    <span class="font-semibold text-slate-700">{{ $reply['from'] }}</span>
                                    <span class="shrink-0">{{ $reply['time'] }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-600 whitespace-pre-line">{{ $reply['text'] }}</p>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-slate-400 text-center py-4">Belum ada komentar dari tim.</p>
                @endif
            </div>
        </aside>
    </div>
    @else
    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600 text-center mb-6">
        Tidak ada masalah yang menunggu review saat ini.
    </div>
    @endif

    @if($totalHistory > 0)
    @include('partials.filter-bar', [
        'action' => route('dosen.problem-review', $project['id']),
        'filters' => [
            ['name' => 'status', 'label' => 'Status Masalah', 'value' => $statusFilter, 'options' => [
                'submitted' => 'Menunggu review',
                'revision' => 'Perlu perbaikan',
                'done' => 'Disetujui',
            ]],
        ],
        'summary' => 'Menampilkan '.$history->count().' dari '.$totalHistory.' masalah pada riwayat.',
    ])
    @endif

    @if($history->isNotEmpty())
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Riwayat masalah</h3>
        <ul class="space-y-3">
            @foreach($history as $item)
            <li class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <div class="flex justify-between items-start gap-2">
                    <p class="font-semibold text-slate-800">{{ $item->title }}</p>
                    <span class="text-[10px] font-bold uppercase shrink-0
                        @if($item->board_status === 'done') text-emerald-600
                        @elseif($item->board_status === 'revision') text-red-600
                        @elseif($item->board_status === 'submitted') text-amber-600
                        @else text-slate-500 @endif">
                        {{ match($item->board_status) {
                            'submitted' => 'Diajukan',
                            'revision' => 'Perbaiki',
                            'done' => 'Selesai',
                            default => $item->board_status,
                        } }}
                    </span>
                </div>
                @if($item->lecturer_feedback)
                <p class="text-xs text-slate-500 mt-2"><span class="font-semibold">Catatan dosen:</span> {{ $item->lecturer_feedback }}</p>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
