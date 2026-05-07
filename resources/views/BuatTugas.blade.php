@extends('layouts.app')

@section('title', 'Tambah Tugas - DELPRO')

@section('content')
<div class="p-6 space-y-6">
                <!-- Judul Projek & Gear -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
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
                    <!-- Navigasi Ikon Samping -->
                    <div class="w-24 space-y-4">
                        @foreach([['icon'=>'fa-comments','active'=>false], ['icon'=>'fa-tasks','active'=>true], ['icon'=>'fa-calendar-alt','active'=>false], ['icon'=>'fa-project-diagram','active'=>false], ['icon'=>'fa-clipboard-check','active'=>false]] as $menu)
                        <div class="bg-{{ $menu['active'] ? 'blue-200' : 'white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center cursor-pointer">
                            <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                        </div>
                        @endforeach
                    </div>

                    <!-- FORM TAMBAH TUGAS -->
                    <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-gray-100 p-12">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-3xl font-bold text-gray-800">Tambah Tugas</h3>
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-blue-600">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>

                        <form action="{{ route('simpan-tugas', $id) }}" method="POST" class="space-y-8 max-w-4xl">
                            @csrf
                            <!-- Judul Tugas -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Judul Tugas</label>
                                <div class="col-span-3">
                                    <input type="text" name="judul_tugas" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 outline-none focus:border-blue-400" required>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="grid grid-cols-4 items-start">
                                <label class="text-sm font-bold text-gray-700 pt-2">Deskripsi Tugas</label>
                                <div class="col-span-3">
                                    <textarea name="deskripsi" rows="5" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 resize-none" required></textarea>
                                </div>
                            </div>

                            <!-- Tanggal -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Tanggal Mulai</label>
                                <div class="col-span-1 relative flex items-center">
                                    <input type="date" name="tgl_mulai" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 outline-none focus:border-blue-400 bg-white" required>
                                </div>
                                <label class="text-sm font-bold text-gray-700 text-center">Tanggal Selesai</label>
                                <div class="col-span-1 relative flex items-center">
                                    <input type="date" name="tgl_selesai" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 outline-none focus:border-blue-400 bg-white" required>
                                </div>
                            </div>

                            <!-- Penanggung Jawab -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Penanggung Jawab</label>
                                <div class="col-span-1 relative">
                                    <select name="pj" class="w-full appearance-none border-2 border-gray-200 rounded-xl px-4 py-2 outline-none focus:border-blue-400 bg-white cursor-pointer" required>
                                        <option value="">Pilih Anggota</option>
                                        @foreach($members as $m)
                                            <option value="{{ $m }}">{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="flex justify-end space-x-4 pt-6">
                                <a href="{{ route('penyusunan', $id) }}" class="bg-gray-300 text-gray-700 px-8 py-2 rounded-lg font-bold text-xs hover:bg-gray-400 transition">Batal</a>
                                <button type="submit" class="bg-blue-500 text-white px-8 py-2 rounded-lg font-bold text-xs hover:bg-blue-600 transition shadow-lg shadow-blue-100">Tambah Tugas</button>
                            </div>
                        </form>
                    </div>
                </div>
</div>
@endsection