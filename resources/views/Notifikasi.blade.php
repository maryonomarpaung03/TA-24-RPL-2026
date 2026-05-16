@extends('layouts.app')

@section('title', 'Notifikasi - DELPRO')

@section('content')
<div class="flex-1 p-6">

    <div class="bg-white p-5 rounded shadow">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="font-bold text-gray-700 text-xs uppercase">Notifikasi</h3>
            <span class="text-[10px] text-gray-400">{{ count($notifications) }} pesan</span>
        </div>

        @forelse($notifications as $note)
        <article class="mb-4 last:mb-0 p-3 rounded hover:bg-gray-50 {{ $note->read_at ? '' : 'bg-blue-50/50' }}">
            <div class="flex justify-between items-start gap-3">
                <div class="min-w-0">
                    <h4 class="text-sm font-bold text-gray-800">{{ $note->title }}</h4>
                    <p class="text-[10px] text-gray-500 mt-1">{{ $note->message }}</p>
                    @if($note->project_name)
                    <p class="text-[10px] text-gray-400 mt-1">Proyek: {{ $note->project_name }}</p>
                    @endif
                </div>
                <time class="text-[10px] text-gray-400 shrink-0">
                    {{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}
                </time>
            </div>
            @if(auth()->user()->role === 'lecturer' && $note->project_id)
                <a href="{{ route('dosen.persetujuan.show', $note->project_id) }}"
                   class="inline-block mt-2 text-blue-500 text-xs font-bold hover:underline">Lihat proyek &rarr;</a>
            @elseif($note->project_id && auth()->user()->role !== 'lecturer')
                <a href="{{ route('dashboard', ['project_id' => $note->project_id]) }}"
                   class="inline-block mt-2 text-blue-500 text-xs font-bold hover:underline">Buka proyek &rarr;</a>
            @endif
        </article>
        @empty
        <p class="text-sm text-gray-400 text-center py-8">Belum ada notifikasi.</p>
        @endforelse
    </div>
</div>
@endsection
