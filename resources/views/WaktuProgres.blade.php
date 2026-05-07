@extends('layouts.app')

@section('title', 'Waktu Progres - DELPRO')
@section('main_class', 'flex-1 flex flex-col overflow-hidden')

@push('head')
<style>
    .gantt-grid { display: grid; grid-template-columns: repeat({{ $daysInMonth }}, minmax(30px, 1fr)); border-left: 1px solid #e5e7eb; }
    .gantt-col { border-right: 1px solid #e5e7eb; height: 500px; }
    .truncate-custom { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .scroll-container { max-height: 600px; overflow-y: auto; overflow-x: auto; }
</style>
@endpush

@section('content')
<div class="p-6 space-y-6 overflow-y-auto">
                <!-- 2 & 3. Judul & Breadcrumb -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-blue-500">waktu progres</span>
                        </nav>
                    </div>
                    <div class="relative" x-data="{ openGear: false }">
                        <button @click="openGear = !openGear" class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                        <div x-show="openGear" @click.outside="openGear = false" class="absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-xl z-50 overflow-hidden">
                            <a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">Detail Projek</a>
                            <a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">Edit Projek</a>
                            <a href="{{ route('projek-saya') }}" class="block px-4 py-3 text-sm text-red-500 hover:bg-red-50">Keluar</a>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-6">
                    <!-- 5. Sidebar Navigasi Ikon -->
                    <div class="w-24 space-y-4">
                        @php
                            $menus = [
                                ['icon' => 'fa-comments', 'color' => 'bg-white', 'label' => 'Dekomposisi Masalah', 'route' => route('dekomposisi', $id)],
                                ['icon' => 'fa-tasks', 'color' => 'bg-white', 'label' => 'Penyusunan Rencana', 'route' => route('penyusunan', $id)],
                                ['icon' => 'fa-calendar-alt', 'color' => 'bg-blue-200', 'label' => 'Waktu Progres', 'route' => '#'],
                                ['icon' => 'fa-project-diagram', 'color' => 'bg-white', 'label' => 'Pelaksanaan & Evaluasi', 'route' => '#'],
                                ['icon' => 'fa-clipboard-check', 'color' => 'bg-white', 'label' => 'Penilaian', 'route' => '#']
                            ];
                        @endphp
                        @foreach($menus as $menu)
                        <div class="relative group">
                            <a href="{{ $menu['route'] }}" class="{{ $menu['color'] }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                            </a>
                            <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 shadow-lg">
                                {{ $menu['label'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- 6 & 7. GANTT CHART (WAKTU PROGRES) -->
                    <div class="flex-1 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                        <!-- 6. Filter Bulan -->
                        <div class="flex justify-end items-center mb-6 space-x-2">
                            <label class="text-xs font-bold text-gray-500">Bulan :</label>
                            <form action="" method="GET" class="relative" x-data="{ openMonth: false }">
                                <div class="flex items-center bg-gray-50 border rounded-full px-4 py-1.5 cursor-pointer" @click="openMonth = !openMonth">
                                    <input type="text" value="{{ $monthName }}" readonly class="bg-transparent text-xs font-bold outline-none w-24 cursor-pointer">
                                    <i class="fas fa-chevron-down text-[10px] text-gray-400 ml-2"></i>
                                </div>
                                <div x-show="openMonth" @click.outside="openMonth = false" class="absolute right-0 mt-2 w-40 bg-white border rounded-xl shadow-xl z-50 max-h-48 overflow-y-auto">
                                    @foreach($months as $num => $name)
                                    <a href="?month={{ $num }}" class="block px-4 py-2 text-xs hover:bg-blue-50 {{ $selectedMonth == $num ? 'bg-blue-100 font-bold' : '' }}">{{ $name }}</a>
                                    @endforeach
                                </div>
                            </form>
                        </div>

                        <!-- Area Tabel Waktu -->
                        <div class="scroll-container border rounded-xl relative">
                            <!-- 6. Header Tabel (Bulan & Tanggal) -->
                            <div class="bg-gray-400 text-white text-center py-1 text-sm font-bold border-b border-gray-500">
                                {{ $monthName }} 2026
                            </div>
                            <div class="bg-gray-300 grid grid-cols-{{ $daysInMonth }} text-center border-b border-gray-500">
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                <div class="py-1 text-[10px] font-bold border-r border-gray-400 last:border-r-0">{{ $i }}</div>
                                @endfor
                            </div>

                            <!-- 7. Baris Progres (Tugas) -->
                            <div class="relative bg-white min-h-[500px]">
                                <!-- Kolom Vertikal -->
                                <div class="absolute inset-0 gantt-grid pointer-events-none">
                                    @for($i = 1; $i <= $daysInMonth; $i++)
                                    <div class="gantt-col border-r border-gray-100 last:border-r-0"></div>
                                    @endfor
                                </div>

                                <!-- Bar Tugas -->
                                <div class="relative pt-4 space-y-4">
                                    @foreach($tasks as $task)
                                    @php
                                        // Menghitung posisi kolom (grid-column: start / end)
                                        $gridStart = $task['start'];
                                        $gridSpan = ($task['end'] - $task['start']) + 1;
                                    @endphp
                                    <div class="grid grid-cols-{{ $daysInMonth }} h-8">
                                        <div style="grid-column: {{ $gridStart }} / span {{ $gridSpan }};" 
                                             class="{{ $task['color'] }} rounded-lg flex items-center px-3 shadow-md border border-black/10 z-10">
                                            <span class="text-[9px] font-bold text-white truncate-custom">
                                                {{ $task['name'] }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- 6c. Anggota Aktif di Pojok Kanan Atas Tabel -->
                        <div class="flex justify-end -mt-[580px] pr-4 relative z-20">
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-blue-600 shadow-sm">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
 </div>
@endsection