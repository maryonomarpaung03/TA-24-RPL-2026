@extends('layouts.app')

@section('title', 'Penilaian - DELPRO')
@section('root_data', '{ sidebarOpen: true, tab: "kelompok" }')

@section('content')
@php
    $projectTitle = $namaProjek ?? ($groupData['project'] ?? 'Projek Tidak Ditemukan');
    $anggotaList = $anggota ?? collect($groupData['members'] ?? [])->pluck('name')->all();
@endphp
<div class="p-6 space-y-6">
                <!-- Project Header -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $projectTitle }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-gray-400">Pelaksanaan dan evaluasi</span>
                        </nav>
                    </div>
                    <button class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                </div>

                <div class="flex space-x-12">
                    <!-- Navigasi Ikon Samping (1-5) -->
                    <div class="w-24 space-y-4">
                        @php
                            $menus = [
                                ['icon' => 'fa-comments', 'label' => 'Dekomposisi Masalah', 'r' => 'dekomposisi'],
                                ['icon' => 'fa-tasks', 'label' => 'Penyusunan Rencana', 'r' => 'penyusunan'],
                                ['icon' => 'fa-calendar-alt', 'label' => 'Waktu Progres', 'r' => 'waktu-progres'],
                                ['icon' => 'fa-project-diagram', 'label' => 'Pelaksanaan & Evaluasi', 'r' => 'pelaksanaan'],
                                ['icon' => 'fa-clipboard-check', 'label' => 'Penilaian', 'r' => '#']
                            ];
                        @endphp
                        @foreach($menus as $index => $m)
                            <div class="relative group">
                                <a href="{{ $m['r'] != '#' ? route($m['r'], $id) : '#' }}" 
                                   class="{{ $index == 4 ? 'bg-blue-200 shadow-inner' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                    <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                                </a>
                                <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Form Penilaian -->
                    <div class="flex-1">
                        <!-- Tab Navigation -->
                        <div class="flex space-x-8 border-b border-gray-200 mb-8 text-sm font-bold">
                            <button @click="tab = 'kelompok'" :class="tab == 'kelompok' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'" class="pb-2 transition-all">Penilaian Kelompok</button>
                            <a href="{{ route('penilaian-dosen-status', $id) }}" class="text-gray-400 pb-2 hover:text-blue-600 transition-all">Penilaian Dosen</a>
                        </div>

                        <!-- Card Container -->
                        <div class="bg-white rounded-[2.5rem] p-12 shadow-sm border border-gray-100 max-w-4xl">
                            <form action="#" class="space-y-10">
                                <!-- Bagian 1: Penilaian Kelompok -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Penilaian Kelompok</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <input type="number" min="10" max="100" class="border border-gray-400 rounded-lg p-2 w-16 text-center outline-none focus:border-blue-400 shadow-inner">
                                </div>

                                <!-- Bagian 2: Penilaian Individu -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Penilaian Individu</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <div class="max-w-md border border-gray-400 rounded-lg overflow-hidden">
                                        <table class="w-full text-xs">
                                            <tr class="bg-white border-b border-gray-400">
                                                <th class="p-3 text-left font-bold border-r border-gray-400 uppercase w-2/3">Nama</th>
                                                <th class="p-3 text-left font-bold uppercase">Nilai</th>
                                            </tr>
                                            @foreach($anggotaList as $nama)
                                            <tr class="border-b border-gray-400 last:border-b-0">
                                                <td class="p-3 border-r border-gray-400 font-medium">{{ $nama }}</td>
                                                <td class="p-1">
                                                    <input type="number" min="10" max="100" class="w-full p-2 outline-none text-center bg-transparent">
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>

                                <!-- Bagian 3: Refleksi -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Refleksi</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <textarea maxlength="500" placeholder="Ketikkkan refleksi anda disini..." 
                                              class="w-full border border-gray-400 rounded-2xl p-4 h-28 resize-none outline-none focus:border-blue-400 transition text-xs italic shadow-inner"></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end space-x-4 pt-4">
                                    <button type="button" class="bg-gray-300 px-8 py-2 rounded-full text-xs font-bold text-gray-700 hover:bg-gray-400 transition">Batal</button>
                                    <button type="submit" class="bg-blue-600 px-8 py-2 rounded-full text-xs font-bold text-white hover:bg-blue-700 transition shadow-lg shadow-blue-200">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
 </div>
@endsection