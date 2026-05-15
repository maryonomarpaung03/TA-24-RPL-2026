@extends('layouts.app')

@section('title', 'Detail Proyek - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="p-8 max-w-4xl mx-auto">
    <a href="{{ route('dosen.persetujuan') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700 mb-6">
        <i class="fas fa-arrow-left text-xs"></i> Kembali ke daftar persetujuan
    </a>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b bg-slate-50">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-600 mb-2">Menunggu Persetujuan</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ $project['name'] }}</h1>
            <p class="text-sm text-slate-500 mt-2">
                Diajukan {{ $project['submitted_at'] ?? '-' }}
                oleh {{ $project['creator_name'] }} ({{ $project['creator_email'] }})
            </p>
        </div>

        <div class="px-8 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400 mb-1">Nama Kelompok</p>
                    <p class="text-slate-900 font-medium">{{ $project['group_name'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400 mb-1">Mata Kuliah</p>
                    <p class="text-slate-900 font-medium">{{ $project['course_name'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400 mb-1">Rencana Realisasi</p>
                    <p class="text-slate-900 font-medium">{{ $project['planned_months'] ?? '-' }} bulan</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400 mb-1">Periode</p>
                    <p class="text-slate-900 font-medium">{{ $project['start_date'] ?? '-' }} — {{ $project['end_date'] ?? '-' }}</p>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase text-slate-400 mb-1">Masalah Utama</p>
                <p class="text-slate-800">{{ $project['masalah'] ?: '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase text-slate-400 mb-1">Deskripsi Proyek</p>
                <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">{{ $project['deskripsi'] ?: '-' }}</p>
            </div>

            @if(!empty($project['attachment_url']))
            <div>
                <p class="text-xs font-bold uppercase text-slate-400 mb-1">Lampiran</p>
                <a href="{{ $project['attachment_url'] }}" target="_blank" rel="noopener"
                   class="text-sm font-semibold text-blue-600 hover:underline">Buka lampiran</a>
            </div>
            @endif

            <div>
                <p class="text-xs font-bold uppercase text-slate-400 mb-2">Anggota Tim</p>
                @if(count($project['members']) > 0)
                    <ul class="space-y-2">
                        @foreach($project['members'] as $member)
                            <li class="text-sm text-slate-700 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ $member['initials'] }}</span>
                                {{ $member['name'] }} <span class="text-slate-400">({{ $member['email'] }})</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-slate-500">Tidak ada anggota tambahan.</p>
                @endif
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 text-sm text-slate-600">
                <p><span class="font-semibold">Dosen pengampu:</span> {{ $project['lecturer_name'] ?? auth()->user()->displayName() }}</p>
                <p class="mt-1"><span class="font-semibold">Email:</span> {{ $project['lecturer_email'] }}</p>
            </div>
        </div>

        @if($project['status'] === 'pending_approval')
        <div class="px-8 py-6 border-t bg-slate-50 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
            <a href="{{ route('dosen.persetujuan') }}"
               class="text-center rounded-full border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                Kembali
            </a>
            <form method="POST" action="{{ route('dosen.persetujuan.approve', $project['id']) }}">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto rounded-full bg-emerald-600 text-white px-8 py-2.5 text-sm font-bold hover:bg-emerald-700 transition shadow-sm">
                    <i class="fas fa-check mr-1"></i> Setujui Proyek
                </button>
            </form>
        </div>
        @elseif($project['status'] === 'active')
        <div class="px-8 py-6 border-t bg-green-50 text-sm text-green-800 font-medium">
            Proyek ini sudah disetujui.
        </div>
        @endif
    </div>
</div>
@endsection
