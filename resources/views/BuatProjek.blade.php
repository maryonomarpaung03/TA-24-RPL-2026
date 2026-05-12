@extends('layouts.app')

@section('title', 'Buat Projek - DELPRO')
@section('body_class', 'bg-gray-50 font-sans')

@section('content')
<div class="p-8 flex flex-col items-center">
    <div class="w-full max-w-4xl">

        <!-- Heading -->
        <h2 class="text-3xl font-bold text-gray-900 mb-8">
            Buat Projek
        </h2>

        <!-- Alert Success -->
        @if(session('success'))
            <div class="mb-6 rounded-xl bg-green-100 border border-green-300 text-green-700 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Validation -->
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-100 border border-red-300 text-red-700 px-4 py-3">
                <h4 class="font-semibold mb-2">
                    Terdapat kesalahan pada form:
                </h4>

                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white rounded-[2rem] border border-gray-100 p-10 shadow-sm">

            <!-- Petunjuk Pengisian -->
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-8">
                <h4 class="font-bold text-gray-800 mb-3">
                    Petunjuk Pengisian Form Buat Projek
                </h4>

                <ul class="text-sm text-gray-600 space-y-2 list-decimal list-inside leading-relaxed">
                    <li>
                        Judul harus singkat, jelas, dan sesuai dengan ruang lingkup pembelajaran
                    </li>

                    <li>
                        Masalah utama adalah hal yang benar-benar terjadi dan bisa dibuktikan secara nyata (bukan asumsi)
                    </li>

                    <li>
                        Deskripsi masalah menjelaskan konteks dan mengapa masalah itu penting untuk diselesaikan, sertakan bukti, contoh, atau observasi (Jika ada)
                    </li>

                    <li>
                        Lampiran opsional: unggah foto, dokumen PDF, atau Word sebagai pendukung proyek
                    </li>
                </ul>
            </div>

            <!-- FORM -->
            <form action="{{ route('simpan-projek') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-6">

                @csrf

                <!-- Judul Proyek -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Judul Proyek
                    </label>

                    <input
                        type="text"
                        name="judul"
                        value="{{ old('judul') }}"
                        placeholder="Masukkan judul proyek anda"
                        class="w-full bg-white border rounded-xl px-4 py-3 outline-none transition
                        @error('judul')
                            border-red-400 focus:border-red-500
                        @else
                            border-gray-200 focus:border-blue-400
                        @enderror"
                    >

                    @error('judul')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Masalah Utama -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Masalah Utama
                    </label>

                    <input
                        type="text"
                        name="masalah"
                        value="{{ old('masalah') }}"
                        placeholder="Apa masalah utama yang ingin diselesaikan?"
                        class="w-full bg-white border rounded-xl px-4 py-3 outline-none transition
                        @error('masalah')
                            border-red-400 focus:border-red-500
                        @else
                            border-gray-200 focus:border-blue-400
                        @enderror"
                    >

                    @error('masalah')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Deskripsi
                    </label>

                    <textarea
                        name="deskripsi"
                        rows="6"
                        placeholder="Jelaskan deskripsi proyek secara detail..."
                        class="w-full bg-white border rounded-xl px-4 py-4 outline-none transition resize-none
                        @error('deskripsi')
                            border-red-400 focus:border-red-500
                        @else
                            border-gray-200 focus:border-blue-400
                        @enderror"
                    >{{ old('deskripsi') }}</textarea>

                    @error('deskripsi')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Lampiran Pendukung -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Lampiran Pendukung
                        <span class="font-normal text-gray-400">
                            (opsional)
                        </span>
                    </label>

                    <p class="text-xs text-gray-500 mb-3">
                        Unggah foto, PDF, atau dokumen lain sebagai bukti atau pendukung proyek.
                        Beberapa file diperbolehkan.
                    </p>

                    <label class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 cursor-pointer hover:border-blue-300 hover:bg-blue-50/40 transition">

                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>

                        <span class="text-sm font-semibold text-gray-600">
                            Klik untuk memilih file atau seret ke sini
                        </span>

                        <span class="text-xs text-gray-400">
                            PDF, JPG, PNG, GIF, DOC, DOCX — maks. 10 MB per file
                        </span>

                        <input
                            type="file"
                            name="lampiran[]"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,application/pdf,image/*"
                            class="sr-only"
                        >
                    </label>

                    @error('lampiran')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('lampiran.*')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Tombol Aksi -->
                <div class="flex justify-end space-x-4 pt-4">

                    <!-- Tombol Batal -->
                    <a href="{{ route('projek-saya') }}"
                       class="bg-gray-300 text-gray-700 px-8 py-2 rounded-full font-bold text-sm hover:bg-gray-400 transition">
                        Batal
                    </a>

                    <!-- Tombol Submit -->
                    <button type="submit"
                            class="bg-blue-600 text-white px-8 py-2 rounded-full font-bold text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                        Tambah Proyek
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>
@endsection