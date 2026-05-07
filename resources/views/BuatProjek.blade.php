@extends('layouts.app')

@section('title', 'Buat Projek - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="p-8 flex flex-col items-center">
                <div class="w-full max-w-4xl">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Buat Projek</h2>

                    <!-- Form Card -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 p-10 shadow-sm">
                        <!-- Petunjuk Pengisian -->
                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-8">
                            <h4 class="font-bold text-gray-800 mb-3">Petunjuk Pengisian Form Buat Projek</h4>
                            <ul class="text-sm text-gray-600 space-y-2 list-decimal list-inside leading-relaxed">
                                <li>Judul harus singkat, jelas, dan sesuai dengan ruang lingkup pembelajaran</li>
                                <li>Masalah utama adalah hal yang benar-benar terjadi dan bisa dibuktikan secara nyata (bukan asumsi)</li>
                                <li>Deskripsi masalah menjelaskan konteks dan mengapa masalah itu penting untuk diselesaikan, sertakan bukti, contoh, atau observasi (Jika ada)</li>
                            </ul>
                        </div>

                        <form action="{{ route('simpan-projek') }}" method="POST" class="space-y-6">
                            @csrf
                            <!-- Judul Proyek -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Judul Proyek</label>
                                <input type="text" name="judul" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 transition" placeholder="Masukkan judul proyek anda">
                            </div>

                            <!-- Masalah Utama -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Masalah utama</label>
                                <input type="text" name="masalah" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 transition" placeholder="Apa masalah utama yang ingin diselesaikan?">
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                                <textarea name="deskripsi" rows="6" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-4 outline-none focus:border-blue-400 transition resize-none" placeholder="Jelaskan deskripsi proyek secara detail..."></textarea>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="flex justify-end space-x-4 pt-4">
                                <a href="{{ route('projek-saya') }}" class="bg-gray-300 text-gray-700 px-8 py-2 rounded-full font-bold text-sm hover:bg-gray-400 transition">Batal</a>
                                <button type="submit" class="bg-blue-600 text-white px-8 py-2 rounded-full font-bold text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-200">Tambah Tugas</button>
                            </div>
                        </form>
                    </div>
                </div>
 </div>
@endsection