@extends('layouts.app')

@section('title', 'Detail Proyek - DELPRO')

@section('content')
<div class="w-full">

    <a href="{{ route('dosen.persetujuan') }}" class="text-blue-500 text-xs font-bold hover:underline mb-4 inline-block">
        &larr; Kembali ke daftar persetujuan
    </a>

    @include('partials.flash-messages')

    <div class="bg-white p-5 rounded shadow">
        <div class="mb-4 border-b pb-4">
            <p class="text-[10px] font-bold text-amber-600 uppercase mb-1">
                {{ $project['status'] === 'pending_revision' ? 'Review Perubahan Proyek' : 'Menunggu Persetujuan' }}
            </p>
            <h1 class="text-xl font-bold text-gray-800">{{ $project['name'] }}</h1>
            <p class="text-[10px] text-gray-400 mt-2">
                Diajukan {{ $project['submitted_at'] ?? '-' }}
                &middot; {{ $project['creator_name'] }} ({{ $project['creator_email'] }})
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Nama Kelompok</p>
                <p class="text-sm font-bold text-gray-800">{{ $project['group_name'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Mata Kuliah</p>
                <p class="text-sm font-bold text-gray-800">{{ $project['course_name'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Rencana Realisasi</p>
                <p class="text-sm font-bold text-gray-800">{{ $project['planned_months'] ?? '-' }} bulan</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Periode</p>
                <p class="text-sm font-bold text-gray-800">{{ $project['start_date'] ?? '-' }} &mdash; {{ $project['end_date'] ?? '-' }}</p>
            </div>
        </div>

        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Masalah Utama</p>
            <p class="text-sm text-gray-700">{{ $project['masalah'] ?: '-' }}</p>
        </div>

        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi Proyek</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $project['deskripsi'] ?: '-' }}</p>
        </div>

        @if(!empty($project['attachment_url']))
        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Lampiran</p>
            <a href="{{ $project['attachment_url'] }}" target="_blank" rel="noopener"
               class="text-blue-500 text-xs font-bold hover:underline">Buka lampiran</a>
        </div>
        @endif

        <div class="mb-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Anggota Tim</p>
            @if(count($project['members']) > 0)
                <ul class="space-y-2">
                    @foreach($project['members'] as $member)
                    <li class="text-sm text-gray-700 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ $member['initials'] }}</span>
                        {{ $member['name'] }} <span class="text-gray-400">({{ $member['email'] }})</span>
                    </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-400">Tidak ada anggota tambahan.</p>
            @endif
        </div>

        <div class="rounded bg-gray-50 border border-gray-100 px-4 py-3 text-[10px] text-gray-500 mb-4">
            <p><span class="font-bold text-gray-600">Dosen pengampu:</span> {{ $project['lecturer_name'] ?? auth()->user()->displayName() }}</p>
            <p class="mt-1"><span class="font-bold text-gray-600">Email:</span> {{ $project['lecturer_email'] }}</p>
        </div>

        @if(in_array($project['status'], ['pending_approval', 'pending_revision'], true))
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 mb-4">
            @if($project['status'] === 'pending_revision')
            Tim mengajukan <strong>perubahan data proyek</strong>. Proyek tetap berjalan; setujui agar perubahan resmi tercatat.
            @else
            Pengajuan proyek baru menunggu persetujuan Anda.
            @endif
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('dosen.persetujuan') }}"
               class="text-center border border-gray-300 bg-white px-5 py-2 rounded text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                Kembali
            </a>
            <form method="POST" action="{{ route('dosen.persetujuan.approve', $project['id']) }}">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto bg-emerald-600 text-white px-6 py-2 rounded text-xs font-bold hover:bg-emerald-700 transition">
                    {{ $project['status'] === 'pending_revision' ? 'Setujui Perubahan' : 'Setujui Proyek' }}
                </button>
            </form>
        </div>
        @elseif(in_array($project['status'], ['active', 'completed'], true))
        <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <p class="text-sm text-emerald-700 font-bold">Proyek ini sudah disetujui.</p>
            <a href="{{ route('dosen.proyek-mahasiswa.show', $project['id']) }}"
               class="text-center bg-blue-600 text-white px-5 py-2 rounded text-xs font-bold hover:bg-blue-700 transition">
                Kelola di Proyek Mahasiswa &rarr;
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
