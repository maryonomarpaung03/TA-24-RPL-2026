@extends('layouts.app')

@section('title', 'Proyek Saya - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="w-full">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Proyek Saya</h2>
            <p class="text-gray-500">Kelola semua proyek akademik Anda</p>
        </div>
        <a href="{{ route('buat-projek') }}" class="bg-black text-white px-6 py-2.5 rounded-full font-bold hover:bg-gray-800 transition">+ Buat Proyek</a>
    </div>

    @include('partials.filter-bar', [
        'action' => route('my-project'),
        'search' => [
            'name' => 'search',
            'value' => $keyword,
            'placeholder' => 'Cari judul, kelompok, atau mata kuliah',
        ],
        'filters' => [
            ['name' => 'status', 'label' => 'Status', 'value' => $filterState['status'], 'options' => $statusOptions],
            ['name' => 'kelas', 'label' => 'Kelas', 'value' => $filterState['kelas'], 'options' => $classOptions],
            ['name' => 'dosen', 'label' => 'Dosen', 'value' => $filterState['dosen'], 'options' => $lecturerOptions],
            ['name' => 'peran', 'label' => 'Peran Saya', 'value' => $filterState['peran'], 'options' => [
                'pm' => 'Project Manager',
                'anggota' => 'Anggota',
            ]],
        ],
        'summary' => 'Menampilkan '.count($projects).' dari '.$totalProjects.' proyek.',
    ])

    @if(count($projects) === 0)
                <div class="bg-white rounded-2xl border p-10 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="font-bold text-gray-700">Proyek tidak ditemukan</h3>
                    <p class="text-gray-500 mt-2">Coba ubah kata kunci atau reset filter.</p>
                </div>
            @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($projects as $p)
        <article class="bg-white rounded-2xl border p-4 shadow-sm hover:shadow-md transition">
            <div class="text-[10px] font-black uppercase mb-2 {{ $p['status'] === 'Draft' ? 'text-slate-500' : ($p['status'] === 'In Review' || $p['status'] === 'Review Perubahan' ? 'text-amber-600' : ($p['status'] === 'Done' ? 'text-emerald-600' : ($p['status'] === 'Planning' || $p['status'] === 'Rejected' || $p['status'] === 'Archived' ? 'text-gray-500' : 'text-blue-600'))) }}">{{ $p['label'] }}</div>
            <a href="{{ route('problem-identification', $p['id']) }}" class="font-bold text-gray-900 hover:text-blue-600 transition line-clamp-2">{{ $p['name'] }}</a>
            <p class="text-xs text-gray-500 mt-2 mb-4 line-clamp-2">{{ $p['description'] }}</p>
            @php
                // Selesai = hijau, di bawah setengah jalan = oranye (perlu perhatian), sisanya biru.
                [$progressText, $progressBar] = match (true) {
                    $p['status'] === 'Done' => ['text-emerald-600', 'bg-emerald-500'],
                    $p['progress'] < 50 => ['text-orange-500', 'bg-orange-500'],
                    default => ['text-blue-600', 'bg-blue-600'],
                };
            @endphp
            <div class="flex items-baseline justify-between mb-2">
                <span class="text-[10px] font-black text-gray-400 uppercase">Progress</span>
                <span class="text-[10px] font-black {{ $progressText }}">{{ $p['progress'] }}%</span>
            </div>
            <div class="w-full bg-gray-100 h-1.5 rounded-full mb-1.5">
                <div class="h-full rounded-full {{ $progressBar }}" style="width: {{ $p['progress'] }}%"></div>
            </div>
            <p class="text-[10px] text-gray-400 mb-4 truncate">
                @if($p['status'] === 'Done')
                    Seluruh tahapan selesai
                @else
                    Tahap berjalan: {{ $p['progress_stage'] }}
                @endif
            </p>
            <div class="space-y-3">
                @include('partials.project-manage-actions', [
                    'canManage' => $p['can_manage'] ?? false,
                    'projectId' => $p['id'],
                    'class' => 'pt-1',
                ])
                <div class="flex items-center justify-between">
                    <div class="flex -space-x-2">
                        @foreach(array_slice($p['members'], 0, 2) as $m)
                            <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-blue-600">{{ $m }}</div>
                        @endforeach
                    </div>
                    <a href="{{ route('problem-identification', $p['id']) }}" class="text-sm font-bold text-blue-600 hover:text-blue-700">Details <i class="fas fa-chevron-right text-[10px]"></i></a>
                </div>
            </div>
        </article>
        @endforeach

        <a href="{{ route('buat-projek') }}" class="bg-white rounded-2xl border-2 border-dashed border-gray-300 p-6 min-h-[200px] flex flex-col items-center justify-center text-center hover:border-blue-400 hover:bg-blue-50 transition">
            <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-4"><i class="fas fa-plus"></i></div>
            <h3 class="font-bold text-gray-700 mb-2">Mulai Proyek Baru</h3>
            <p class="text-sm text-gray-500">Buat ruang kolaborasi baru untuk ide penelitian Anda.</p>
        </a>
    </div>
</div>
@endif
@if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-10 right-10 bg-red-600 text-white px-8 py-4 rounded-2xl shadow-2xl z-[100] flex items-center space-x-3 transition-all max-w-md">
        <i class="fas fa-exclamation-circle text-xl"></i>
        <span class="font-bold">{{ session('error') }}</span>
    </div>
@endif

@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="fixed bottom-10 right-10 bg-green-600 text-white px-8 py-4 rounded-2xl shadow-2xl z-[100] flex items-center space-x-3 transition-all">
        <i class="fas fa-check-circle text-xl"></i>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
@endif
@endsection
