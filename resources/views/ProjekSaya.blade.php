@extends('layouts.app')

@section('title', 'Projek Saya - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="w-full" x-data="{ statusFilter: 'all' }">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Projek Saya</h2>
            <p class="text-gray-500">Kelola semua proyek akademik Anda</p>
        </div>
        <a href="{{ route('buat-projek') }}" class="bg-black text-white px-6 py-2.5 rounded-full font-bold hover:bg-gray-800 transition">+ Buat Projek</a>
    </div>

    <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 mb-8">
        <div class="flex-1 relative" x-data="{ openHistory: false }">
            <form method="GET" action="{{ route('my-project') }}">
                <div class="flex items-center bg-gray-50 rounded-full px-6 py-2 border border-transparent focus-within:border-blue-300 transition">
                    <i class="fas fa-search text-gray-400 mr-3"></i>
                    <input 
                        type="text"
                        name="search"
                        value="{{ $keyword ?? '' }}"
                        placeholder="Cari projek"
                        class="bg-transparent w-full outline-none text-sm py-1"
                    >
                    
                </div>
            </form>
            <div class="flex-1 relative" x-data="{ openHistory: false }">
                    <div
                        x-show="openHistory"
                        @click.outside="openHistory = false"
                        class="absolute left-0 right-0 mt-2 bg-white border rounded-2xl shadow-xl z-50 overflow-hidden"
                    >
                        <p class="px-4 py-2 text-[10px] uppercase font-bold text-gray-400 border-b">Pencarian Terakhir</p>

                        @forelse($searchHistory as $h)
                            <a href="{{ route('my-project', ['search' => $h]) }}"class="block px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 border-b border-gray-50">{{ $h }}</a>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-400">Belum ada riwayat pencarian</p>
                        @endforelse
                    </div>
                </div>
            </div>
        <div class="relative" x-data="{ openStatus: false }">
            <button @click="openStatus = !openStatus" class="bg-gray-50 px-6 py-3 rounded-full text-sm font-bold text-gray-700 border flex items-center space-x-2 hover:bg-gray-100">
                <span>Status</span>
                <i class="fas fa-chevron-down text-[10px]"></i>
            </button>
            <div x-show="openStatus" @click.outside="openStatus = false" class="absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-xl z-50">
                <button @click="statusFilter = 'all'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 border-b">Semua</button>
                <button @click="statusFilter = 'draft'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 border-b">Draft</button>
                <button @click="statusFilter = 'in_progress'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 border-b">On Progress</button>
                <button @click="statusFilter = 'on_review'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 border-b">In Review</button>
                <button @click="statusFilter = 'planning'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 border-b">Planning</button>
                <button @click="statusFilter = 'done'; openStatus = false" class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50">Selesai</button>
            </div>
        </div>
    </div>
    
    @if(count($projects) === 0)
                <div class="bg-white rounded-2xl border p-10 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="font-bold text-gray-700">Proyek tidak ditemukan</h3>
                    <p class="text-gray-500 mt-2">Coba gunakan kata kunci lain.</p>
                </div>
            @else            
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($projects as $p)
        <article
            x-show="statusFilter === 'all' || statusFilter === '{{ $p['filter_key'] }}'"
            class="bg-white rounded-2xl border p-4 shadow-sm hover:shadow-md transition"
        >
            <div class="text-[10px] font-black uppercase mb-2 {{ $p['status'] === 'Draft' ? 'text-slate-500' : ($p['status'] === 'In Review' || $p['status'] === 'Review Perubahan' ? 'text-amber-600' : ($p['status'] === 'Done' ? 'text-orange-500' : ($p['status'] === 'Planning' || $p['status'] === 'Rejected' || $p['status'] === 'Archived' ? 'text-gray-500' : 'text-blue-600'))) }}">{{ $p['label'] }}</div>
            <a href="{{ route('problem-identification', $p['id']) }}" class="font-bold text-gray-900 hover:text-blue-600 transition line-clamp-2">{{ $p['name'] }}</a>
            <p class="text-xs text-gray-500 mt-2 mb-4 line-clamp-2">{{ $p['description'] }}</p>
            <div class="text-[10px] font-black text-gray-400 uppercase mb-2">Progress</div>
            <div class="w-full bg-gray-100 h-1.5 rounded-full mb-4">
                <div class="h-full rounded-full {{ $p['status'] === 'Done' ? 'bg-orange-500' : 'bg-blue-600' }}" style="width: {{ $p['progress'] }}%"></div>
            </div>
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
            <h3 class="font-bold text-gray-700 mb-2">Mulai Projek Baru</h3>
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
