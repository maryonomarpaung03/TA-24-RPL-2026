@extends('layouts.app')

@section('title', 'Penilaian Dosen - PjBL')

@section('content')
<div class="w-full space-y-6">
                <!-- Project Name Header Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            projek saya/ <span class="text-gray-400">Pelaksanaan dan evaluasi</span>
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
                                   class="{{ $index == 4 ? 'bg-blue-200' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                    <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                                </a>
                                <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Main Content Area -->
                    <div class="flex-1">
                        <!-- Tabs -->
                        <div class="flex space-x-8 border-b border-gray-200 mb-10 text-sm font-bold">
                            <a href="{{ route('penilaian-kelompok', $id) }}" class="text-gray-800 pb-2 hover:text-blue-600 transition">Penilaian Kelompok</a>
                            <button class="text-blue-600 border-b-2 border-blue-600 pb-2">Penilaian Dosen</button>
                        </div>
                        
                        <!-- Empty State Message -->
                        <div class="flex flex-col items-start pt-10">
                            <h3 class="text-4xl text-gray-600 font-medium leading-tight">
                                Belum ada penilaian yang dilakukan oleh dosen
                            </h3>
                        </div>
                    </div>
                </div>
 </div>
@endsection