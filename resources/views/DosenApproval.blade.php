@extends('layouts.app')

@section('title', 'Approval Project - DELPRO')

@section('content')
<div class="flex-1 p-6">

    @include('partials.flash-messages')

    <div class="bg-white p-5 rounded shadow">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <div>
                <h3 class="font-bold text-gray-700 text-xs uppercase">Approval Project</h3>
                <p class="text-[10px] text-gray-400 mt-1">Diajukan ke: {{ auth()->user()->email }}</p>
            </div>
            <span class="text-sm font-extrabold text-blue-600">{{ count($pending_projects) }} menunggu</span>
        </div>

        @forelse($pending_projects as $project)
        <div class="mb-5 last:mb-0 pb-5 last:pb-0 border-b last:border-b-0 border-gray-100">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-bold text-gray-800">{{ $project['name'] }}</h4>
                    <p class="text-[10px] text-gray-500 mt-1 line-clamp-2">{{ $project['description'] }}</p>
                    <p class="text-[10px] text-gray-400 mt-2">
                        Pengaju: {{ $project['creator_name'] }} ({{ $project['creator_email'] }})
                        @if($project['submitted_at']) &middot; {{ $project['submitted_at'] }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('dosen.persetujuan.show', $project['id']) }}"
                       class="text-blue-500 text-xs font-bold hover:underline">Lihat detail &rarr;</a>
                    <form method="POST" action="{{ route('dosen.persetujuan.approve', $project['id']) }}">
                        @csrf
                        <button type="submit"
                                class="bg-emerald-600 text-white px-4 py-2 rounded text-xs font-bold hover:bg-emerald-700 transition">
                            Setujui
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-8">Belum ada proyek yang diajukan ke email Anda.</p>
        @endforelse
    </div>
</div>
@endsection
