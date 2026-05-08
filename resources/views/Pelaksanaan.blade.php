@extends('layouts.app')

@section('title', 'Pelaksanaan & Evaluasi - DELPRO')
@section('root_data', '{ sidebarOpen: true, commentModal: false, editModal: false, selectedTask: "", activeColumn: null }')

@section('content')
<div class="p-6 space-y-6">
                <!-- Judul & Breadcrumb -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            projek saya/ <span class="text-blue-500">Pelaksanaan dan evaluasi</span>
                        </nav>
                    </div>
                    <button class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                </div>

                <div class="flex space-x-12">

                    <!-- KANBAN BOARD -->
                    <div class="flex-1">
                        <!-- Anggota Aktif -->
                        <div class="flex justify-end mb-4">
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-blue-600 shadow-sm">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-6">
                            @foreach([['key'=>'todo','label'=>'Belum Dikerjakan','color'=>'blue-600'], ['key'=>'doing','label'=>'Belum Dikerjakan','color'=>'yellow-400'], ['key'=>'done','label'=>'Selesai','color'=>'green-500']] as $col)
                            <div class="bg-gray-200/80 rounded-[2rem] p-6 flex flex-col h-[600px]">
                                <div class="flex items-center space-x-2 mb-6">
                                    <div class="w-3 h-3 rounded-full bg-{{ $col['color'] }}"></div>
                                    <h4 class="text-sm font-bold text-gray-700">{{ $col['label'] }}</h4>
                                </div>

                                <div class="flex-1 space-y-4 overflow-y-auto">
                                    @foreach($kanban[$col['key']] as $task)
                                    <div class="bg-white rounded-2xl p-4 shadow-sm relative group transition hover:shadow-md">
                                        <div class="flex justify-between items-start mb-2">
                                            <p class="text-[11px] font-bold text-gray-800 leading-tight w-4/5">{{ $task['name'] }}</p>
                                            <button @click="editModal = true; selectedTask = '{{ $task['name'] }}'" class="text-gray-300 hover:text-blue-500 transition"><i class="fas fa-edit text-xs"></i></button>
                                        </div>
                                        <div class="flex items-center justify-between mt-4">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 text-[8px] flex items-center justify-center font-bold text-blue-600 border border-white">{{ $task['creator'] }}</div>
                                                <span class="text-[8px] px-2 py-0.5 rounded-full font-bold text-white {{ $task['level'] == 'Sulit' ? 'bg-red-500' : ($task['level'] == 'Sedang' ? 'bg-blue-500' : 'bg-green-500') }}">{{ $task['level'] }}</span>
                                            </div>
                                            <button @click="commentModal = true" class="text-[8px] bg-gray-400 text-white px-2 py-0.5 rounded-full font-bold hover:bg-gray-600">Komentar</button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="mt-4" x-data="{ adding: false }">
                                    <button x-show="!adding" @click="adding = true" class="text-blue-500 text-[11px] font-bold hover:underline">Tambah tugas</button>
                                    <input x-show="adding" @focusout="adding = false" @keydown.enter="adding = false" type="text" placeholder="Ketik tugas & enter" class="w-full bg-white border border-blue-300 rounded-xl px-4 py-2 text-xs outline-none shadow-inner">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
 </div>

<!-- MODAL KOMENTAR -->
    <div x-show="commentModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl p-8 w-96 shadow-2xl relative" @click.outside="commentModal = false">
            <button @click="commentModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-black"><i class="fas fa-times"></i></button>
            <h3 class="text-lg font-bold mb-4">Komentar</h3>
            <textarea placeholder="Ketik komentar anda..." class="w-full bg-gray-50 border rounded-xl p-4 text-xs outline-none focus:border-blue-400 h-32 resize-none"></textarea>
            <div class="flex justify-end space-x-3 mt-6">
                <button @click="commentModal = false" class="bg-gray-200 text-gray-600 px-6 py-2 rounded-lg text-xs font-bold transition hover:bg-gray-300">Batal</button>
                <button @click="commentModal = false" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-xs font-bold shadow-md hover:bg-blue-700">Komen</button>
            </div>
        </div>
    </div>

<!-- MODAL EDIT TUGAS -->
    <div x-show="editModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-10 w-full max-w-lg shadow-2xl relative" @click.outside="editModal = false">
            <button @click="editModal = false" class="absolute top-6 right-6 text-gray-400 hover:text-black text-xl"><i class="fas fa-times"></i></button>
            <h3 class="text-2xl font-bold mb-8 text-center text-gray-800">Edit Tugas</h3>
            <form class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Nama Tugas</label>
                    <input type="text" x-model="selectedTask" class="w-full bg-gray-50 border rounded-xl px-4 py-3 text-sm outline-none focus:border-blue-400 shadow-inner">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Tingkat Kesulitan</label>
                    <select class="w-full bg-gray-50 border rounded-xl px-4 py-3 text-sm outline-none appearance-none cursor-pointer">
                        <option>Mudah</option><option>Sedang</option><option>Sulit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Upload Bukti (.png)</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-blue-400 transition cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-2"></i>
                        <p class="text-[10px] text-gray-400 italic">Pilih file atau tarik gambar ke sini</p>
                    </div>
                </div>
                <div class="flex justify-center space-x-4 pt-4">
                    <button type="button" @click="editModal = false" class="bg-gray-100 text-gray-500 px-10 py-2.5 rounded-full text-xs font-bold hover:bg-gray-200 transition">Batal</button>
                    <button type="button" @click="editModal = false" class="bg-blue-600 text-white px-10 py-2.5 rounded-full text-xs font-bold shadow-lg hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </div>
    </div>
@endsection