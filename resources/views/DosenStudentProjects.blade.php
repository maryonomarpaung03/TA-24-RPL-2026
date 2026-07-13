@extends('layouts.app')

@section('title', 'Proyek Mahasiswa - DELPRO')

@section('content')
<div class="w-full space-y-6">

    @include('partials.flash-messages')

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Proyek Mahasiswa</h2>
        <p class="text-sm text-gray-500 mt-1">Semua proyek yang sudah Anda setujui. Gunakan halaman ini untuk memantau, mereview, dan menilai hasil kerja mahasiswa.</p>
    </div>

    @include('partials.filter-bar', [
        'action' => route('dosen.proyek-mahasiswa'),
        'search' => [
            'name' => 'q',
            'value' => $filterState['q'],
            'placeholder' => 'Cari judul proyek atau nama kelompok',
        ],
        'filters' => [
            ['name' => 'kelas', 'label' => 'Kelas', 'value' => $filterState['kelas'], 'options' => $classOptions],
            ['name' => 'matkul', 'label' => 'Mata Kuliah', 'value' => $filterState['matkul'], 'options' => $courseOptions],
            ['name' => 'status', 'label' => 'Status', 'value' => $filterState['status'], 'options' => $statusOptions],
            ['name' => 'penilaian', 'label' => 'Penilaian', 'value' => $filterState['penilaian'], 'options' => [
                'sudah' => 'Sudah dinilai',
                'belum' => 'Belum dinilai',
            ]],
        ],
        'summary' => 'Menampilkan '.count($approved_projects).' dari '.$totalProjects.' proyek.',
    ])

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-5 border-b pb-3">
            <p class="text-xs font-bold text-gray-500 uppercase">Daftar proyek disetujui</p>
            <span class="text-sm font-extrabold text-blue-600">{{ count($approved_projects) }} proyek</span>
        </div>

        @if(count($approved_projects) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($approved_projects as $project)
            <article class="rounded-2xl border border-gray-100 p-4 hover:border-blue-200 hover:shadow-md transition">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-[10px] font-black uppercase {{ $project['status'] === 'completed' ? 'text-emerald-600' : 'text-blue-600' }}">
                        {{ $project['status_label'] }}
                    </span>
                    <span class="text-[10px] text-gray-400">{{ $project['updated_at'] }}</span>
                </div>
                <h3 class="font-bold text-gray-900 leading-snug">{{ $project['name'] }}</h3>
                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $project['description'] }}</p>
                <p class="text-[10px] text-gray-400 mt-3">
                    {{ $project['group_name'] }} &middot; {{ $project['course_name'] }}
                </p>
                <p class="text-[10px] text-gray-400 mt-1">
                    PM: {{ $project['creator_name'] }} &middot; {{ $project['member_count'] }} anggota
                </p>
                <a href="{{ route('dosen.proyek-mahasiswa.show', $project['id']) }}"
                   class="inline-flex items-center gap-1 mt-4 text-sm font-bold text-blue-600 hover:text-blue-700">
                    Kelola proyek <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </article>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-folder-open text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-600">Belum ada proyek yang disetujui</p>
            <p class="text-xs text-gray-400 mt-2">
                Proyek akan muncul di sini setelah Anda menyetujui pengajuan di menu Approval Project.
            </p>
            <a href="{{ route('dosen.persetujuan') }}"
               class="inline-block mt-4 text-xs font-bold text-blue-600 hover:underline">
                Ke Approval Project &rarr;
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
