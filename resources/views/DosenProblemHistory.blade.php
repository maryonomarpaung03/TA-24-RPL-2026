@extends('layouts.app')

@section('title', 'Riwayat Identifikasi Masalah - PjBL')

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Riwayat Identifikasi Masalah</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih proyek untuk melihat ide masalah dan riwayat review mahasiswa.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($projects as $project)
        <a href="{{ route('dosen.problem-review', $project->id) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/40">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Problem Identification</p>
            <h2 class="mt-2 font-bold text-slate-900">{{ $project->title ?: $project->name }}</h2>
            <p class="mt-2 text-xs text-slate-500">{{ $project->group_name ?: 'Kelompok mahasiswa' }}</p>
        </a>
        @empty
        <p class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Belum ada riwayat masalah dari proyek Anda.</p>
        @endforelse
    </div>
</div>
@endsection
