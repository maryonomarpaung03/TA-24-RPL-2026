@extends('layouts.app')

@section('title', 'Penyusunan Rencana - DELPRO')
@section('root_data', '{ sidebarOpen: true, deleteModal: false, taskToDelete: null }')

@push('head')
<style>
    table td, table th { border: 1px solid #e5e7eb; padding: 12px; font-size: 0.75rem; vertical-align: top; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
@endpush

@section('content')
<div class="p-6 space-y-6">
                <!-- 2 & 3. Judul & Breadcrumb -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-blue-500">penyusunan rencana proyek</span>
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
                                ['icon' => 'fa-tasks', 'color' => 'bg-blue-200', 'label' => 'Penyusunan Rencana Proyek', 'route' => '#'],
                                ['icon' => 'fa-calendar-alt', 'color' => 'bg-white', 'label' => 'Waktu Progres', 'route' => '#'],
                                ['icon' => 'fa-project-diagram', 'color' => 'bg-white', 'label' => 'Pelaksanaan & Evaluasi', 'route' => '#'],
                                ['icon' => 'fa-clipboard-check', 'color' => 'bg-white', 'label' => 'Penilaian', 'route' => '#']
                            ];
                        @endphp
                        @foreach($menus as $menu)
                        <div class="relative group">
                            <a href="{{ $menu['route'] }}" class="{{ $menu['color'] }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                            </a>
                            <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50">
                                {{ $menu['label'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- 6. TABEL PENYUSUNAN -->
                    <div class="flex-1 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                        <div class="flex justify-end mb-4">
                            <button class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-700 transition">Tambah Tugas</button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-12 text-center">No</th>
                                        <th class="w-48">Judul Tugas</th>
                                        <th>Deskripsi Tugas</th>
                                        <th class="w-28">Tanggal Mulai</th>
                                        <th class="w-28">Tanggal Selesai</th>
                                        <th class="w-32 text-center">Penanggung jawab</th>
                                        <th class="w-24 text-center">Alat bantu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasks as $task)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="text-center font-bold">{{ $task['no'] }}</td>
                                        <td class="font-bold leading-tight">{{ $task['judul'] }}</td>
                                        <td class="text-gray-500 leading-relaxed">{{ $task['deskripsi'] }}</td>
                                        <td class="text-center font-medium">{{ $task['mulai'] }}</td>
                                        <td class="text-center font-medium">{{ $task['selesai'] }}</td>
                                        <td class="text-center font-bold text-gray-700">{{ $task['pj'] }}</td>
                                        <td>
                                            <!-- 8. Alat Bantu -->
                                            <div class="flex justify-center space-x-3 text-sm">
                                                <button class="text-gray-400 hover:text-blue-500 transition"><i class="fas fa-comment-dots"></i></button>
                                                <button class="text-yellow-400 hover:text-yellow-600 transition"><i class="fas fa-pencil-alt"></i></button>
                                                <button @click="deleteModal = true; taskToDelete = '{{ $task['judul'] }}'" class="text-red-400 hover:text-red-600 transition"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    <!-- Baris Kosong Simulasi Desain -->
                                    @for($i=0; $i<2; $i++)
                                    <tr><td class="h-16"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <!-- 9. PAGINATION -->
                        <div class="mt-6 flex justify-center items-center space-x-4">
                            <button class="text-gray-400 hover:text-blue-600"><i class="fas fa-chevron-left"></i></button>
                            <div class="flex border rounded-lg overflow-hidden bg-white shadow-sm">
                                <button class="px-3 py-1 border-r bg-blue-50 text-blue-600 font-bold text-xs">1</button>
                                @foreach([2,3,4,5,6] as $n)
                                <button class="px-3 py-1 border-r hover:bg-gray-50 text-xs font-medium">{{ $n }}</button>
                                @endforeach
                                <button class="px-3 py-1 text-xs font-medium">..</button>
                            </div>
                            <button class="text-gray-400 hover:text-blue-600"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
 </div>

<!-- 8. MODAL HAPUS -->
    <div x-show="deleteModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl text-center" @click.outside="deleteModal = false">
            <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Yakin ingin menghapus tugas ini?</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed" x-text="taskToDelete"></p>
            <div class="flex space-x-3">
                <button @click="deleteModal = false" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-xl text-xs font-bold hover:bg-gray-200 transition">Batal</button>
                <button @click="deleteModal = false" class="flex-1 bg-red-500 text-white py-2.5 rounded-xl text-xs font-bold hover:bg-red-600 transition">Konfirmasi</button>
            </div>
        </div>
    </div>
@endsection