@extends('layouts.app')

@section('title', 'Notifikasi - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="p-8 max-w-3xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Notifikasi</h2>
        <p class="text-gray-500 mt-2">Pemberitahuan terkait proyek dan persetujuan.</p>
    </div>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        @forelse($notifications as $note)
            <article class="px-6 py-5 border-b last:border-b-0 {{ $note->read_at ? '' : 'bg-blue-50/40' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $note->title }}</p>
                        <p class="text-sm text-slate-600 mt-1">{{ $note->message }}</p>
                        @if($note->project_name)
                            <p class="text-xs text-slate-400 mt-2">Proyek: {{ $note->project_name }}</p>
                        @endif
                    </div>
                    <time class="text-xs text-slate-400 shrink-0">
                        {{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}
                    </time>
                </div>
                @if(auth()->user()->role === 'lecturer' && $note->project_id)
                    <a href="{{ route('dosen.persetujuan.show', $note->project_id) }}"
                       class="inline-block mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        Lihat proyek →
                    </a>
                @elseif($note->project_id && auth()->user()->role !== 'lecturer')
                    <a href="{{ route('dashboard', ['project_id' => $note->project_id]) }}"
                       class="inline-block mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        Buka proyek →
                    </a>
                @endif
            </article>
        @empty
            <p class="px-6 py-16 text-center text-slate-500 text-sm">Belum ada notifikasi.</p>
        @endforelse
    </div>
</div>
@endsection
