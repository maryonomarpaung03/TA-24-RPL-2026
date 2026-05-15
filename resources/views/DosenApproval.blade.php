@extends('layouts.app')

@section('title', 'Persetujuan Proyek - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="p-8 max-w-5xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Persetujuan Proyek Mahasiswa</h2>
        <p class="text-gray-500 mt-2">Daftar proyek yang diajukan ke email Anda: <strong>{{ auth()->user()->email }}</strong></p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b bg-slate-50">
            <h3 class="font-bold text-slate-800">Menunggu Persetujuan</h3>
        </div>

        @forelse($pending_projects as $project)
            <div class="px-6 py-5 border-b last:border-b-0 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="font-bold text-slate-900">{{ $project['name'] }}</p>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $project['description'] }}</p>
                    <p class="text-xs text-slate-400 mt-2">
                        Pengaju: {{ $project['creator_name'] }} ({{ $project['creator_email'] }})
                        @if($project['submitted_at']) · Diajukan: {{ $project['submitted_at'] }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('dosen.persetujuan.show', $project['id']) }}"
                       class="text-sm font-semibold text-blue-600 hover:text-blue-700">Lihat detail</a>
                    <form method="POST" action="{{ route('dosen.persetujuan.approve', $project['id']) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-full bg-emerald-600 text-white px-5 py-2 text-sm font-semibold hover:bg-emerald-700 transition">
                            Setujui
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-6 py-10 text-center text-slate-500 text-sm">Belum ada proyek yang diajukan ke email Anda.</p>
        @endforelse
    </div>
</div>
@endsection
