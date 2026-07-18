@extends('layouts.app')
@section('title', 'Review Stage - PjBL')
@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('dosen.proyek-mahasiswa.show', $project->id) }}" class="text-sm font-bold text-blue-600">&larr; Kembali ke proyek</a>
    @include('partials.flash-messages')
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Stage gate review</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ \App\Services\StageProgressService::label($stage) }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $project->title }}</p>
        <span class="mt-4 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase text-slate-700">{{ $gate?->status ?? 'draft' }}</span>
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            @foreach($summary['items'] as $item)<div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ $item['label'] }}</p><p class="mt-1 font-bold">{{ $item['value'] }}</p></div>@endforeach
        </div>
        @if($gate?->lecturer_note)<div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900"><b>Catatan terakhir:</b> {{ $gate->lecturer_note }}</div>@endif
        @if(in_array($gate?->status, ['submitted', 'under_review', 'revision'], true))
        <form method="POST" action="{{ route('dosen.stage-gate.review', [$project->id, $stage]) }}" class="mt-6 space-y-4">@csrf
            <textarea name="lecturer_note" rows="4" class="w-full rounded-xl border border-slate-200 p-3" placeholder="Komentar dosen. Wajib saat meminta revisi."></textarea>
            <div class="flex gap-3"><button name="action" value="revision" class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-white">Minta Revisi</button><button name="action" value="approve" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white">Approve Stage</button></div>
        </form>
        @elseif($gate?->status === 'approved')<p class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Stage sudah approved dan terkunci permanen.</p>@endif
    </div>
</div>
@endsection
