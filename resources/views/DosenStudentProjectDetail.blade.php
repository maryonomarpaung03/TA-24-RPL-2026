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
                <p class="text-[10px] font-bold uppercase mb-1 {{ $project['status'] === 'completed' ? 'text-orange-500' : 'text-blue-600' }}">
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
        </div>
    </div>
</div>
@endsection
