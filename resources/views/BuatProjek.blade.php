@extends('layouts.app')

@section('title', 'Buat Projek - PjBL')
@section('body_class', 'bg-gray-50 font-sans')

@php
    $inputClass = 'w-full bg-white border rounded-xl px-4 py-3 outline-none transition border-gray-200 focus:border-blue-400';
    $labelClass = 'block text-sm font-bold text-gray-700 mb-2';
    $isEdit = $isEdit ?? false;
    $editMode = $editMode ?? 'draft';
    $defaults = $formDefaults ?? [];
    $classContext = $classContext ?? null;
    $val = fn (string $key, mixed $fallback = '') => old($key, $defaults[$key] ?? $fallback);
@endphp

@section('content')
<div class="w-full">
        <div class="flex items-center gap-3 mb-8">
            <h2 class="text-3xl font-bold text-gray-900">
                @if(!$isEdit)
                    Buat Projek
                @elseif($editMode === 'draft')
                    Edit Projek (Draft)
                @else
                    Edit Projek
                @endif
            </h2>

            {{-- Tooltip Petunjuk Pengisian --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button"
                        @mouseenter="open = true"
                        @mouseleave="open = false"
                        @click="open = !open"
                        class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center hover:bg-blue-200 transition">
                    ?
                </button>

                <div x-show="open"
                     x-cloak
                     x-transition
                     @mouseleave="open = false"
                     class="absolute left-0 top-8 z-50 w-80 rounded-2xl border border-gray-200 bg-white p-4 shadow-lg text-sm text-gray-600">
                    <p class="font-bold text-gray-800 mb-2">Petunjuk Pengisian</p>
                    <ol class="space-y-1.5 list-decimal list-inside leading-relaxed">
                        <li>Isi data kelompok dan mata kuliah sesuai proyek Anda.</li>
                        <li>Email dosen digunakan untuk notifikasi dan persetujuan proyek.</li>
                        @if($isEdit && $editMode !== 'draft')
                        <li>Setelah mengubah data, klik <strong>Ajukan Perbaikan ke Dosen</strong> agar perubahan ditinjau dosen.</li>
                        @else
                        <li><strong>Simpan ke Draft</strong> untuk menyimpan sementara; <strong>Ajukan ke Dosen</strong> langsung mengirim pengajuan.</li>
                        @endif
                    </ol>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-green-100 border border-green-300 text-green-700 px-4 py-3">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-100 border border-red-300 text-red-700 px-4 py-3">
                <h4 class="font-semibold mb-2">Terdapat kesalahan pada form:</h4>
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] border border-gray-100 p-10 shadow-sm">

            <form action="{{ $isEdit ? route('projek.update', $project->id) : route('simpan-projek') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif
                @if($classContext)
                    <input type="hidden" name="academic_class_id" value="{{ $classContext['id'] }}">
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <i class="fas fa-chalkboard mr-1"></i> Proyek ini akan terhubung ke kelas
                        <span class="font-bold">{{ $classContext['name'] }}</span>.
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="{{ $labelClass }}">Judul Proyek</label>
                        <input type="text" name="judul" value="{{ $val('judul') }}" required
                               placeholder="Masukkan judul proyek" class="{{ $inputClass }} @error('judul') border-red-400 @enderror">
                        @error('judul')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Nama Kelompok</label>
                        <input type="text" name="group_name" value="{{ $val('group_name') }}" required
                               placeholder="Contoh: Kelompok 4" class="{{ $inputClass }} @error('group_name') border-red-400 @enderror">
                        @error('group_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="{{ $labelClass }}">Nama Mata Kuliah</label>
                        <input type="text" name="course_name" value="{{ $val('course_name') }}" required
                               placeholder="Contoh: Rekayasa Perangkat Lunak" class="{{ $inputClass }} @error('course_name') border-red-400 @enderror">
                        @error('course_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Rencana Realisasi Proyek (bulan)</label>
                        <input type="number" name="planned_months" value="{{ $val('planned_months', 6) }}" required
                               min="1" max="36" step="1" placeholder="Contoh: 6"
                               class="{{ $inputClass }} @error('planned_months') border-red-400 @enderror">
                        <p class="mt-2 text-xs text-gray-500">Estimasi durasi pengerjaan proyek dalam bulan.</p>
                        @error('planned_months')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Masalah Utama</label>
                    <input type="text" name="masalah" value="{{ $val('masalah') }}" required
                           placeholder="Apa masalah utama yang ingin diselesaikan?" class="{{ $inputClass }} @error('masalah') border-red-400 @enderror">
                    @error('masalah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="{{ $labelClass }}">Nama Dosen Pengampu</label>
                        <input type="text" name="lecturer_name" value="{{ $val('lecturer_name') }}" required
                               placeholder="Nama lengkap dosen" class="{{ $inputClass }} @error('lecturer_name') border-red-400 @enderror">
                        @error('lecturer_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Email Dosen Pengampu</label>
                        <input type="email" name="lecturer_email" value="{{ $val('lecturer_email') }}" required
                               placeholder="dosen@universitas.ac.id" class="{{ $inputClass }} @error('lecturer_email') border-red-400 @enderror">
                        <p class="mt-2 text-xs text-gray-500">Digunakan untuk identifikasi dan notifikasi dosen.</p>
                        @error('lecturer_email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Email Anggota Tim <span class="font-normal text-gray-400">(opsional)</span></label>
                    <textarea name="member_emails" rows="3" placeholder="Pisahkan dengan koma atau baris baru"
                              class="w-full bg-white border border-gray-200 rounded-xl px-4 py-4 outline-none focus:border-blue-400 resize-none">{{ $val('member_emails') }}</textarea>
                    <p class="mt-2 text-xs text-gray-500">Hanya email terdaftar di sistem yang otomatis bergabung.</p>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Deskripsi</label>
                    <textarea name="deskripsi" rows="6" required placeholder="Jelaskan deskripsi proyek secara detail..."
                              class="w-full bg-white border rounded-xl px-4 py-4 outline-none transition resize-none border-gray-200 focus:border-blue-400 @error('deskripsi') border-red-400 @enderror">{{ $val('deskripsi') }}</textarea>
                    @error('deskripsi')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Lampiran Pendukung <span class="font-normal text-gray-400">(opsional)</span></label>

                    @if(($attachmentMedia['has_media'] ?? false))
                    <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500 mb-3">Lampiran saat ini</p>
                        @if(!empty($attachmentMedia['preview_url']))
                        <img src="{{ $attachmentMedia['preview_url'] }}" alt="Lampiran proyek" class="max-h-48 w-full rounded-xl object-cover border border-slate-200">
                        @elseif(!empty($attachmentMedia['attachment_url']))
                        <a href="{{ $attachmentMedia['attachment_url'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:underline">
                            <i class="fas fa-file-alt"></i> Buka lampiran yang sudah diunggah
                        </a>
                        @endif
                    </div>
                    @endif

                    <label class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 cursor-pointer hover:border-blue-300 hover:bg-blue-50/40 transition">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                        <span class="text-sm font-semibold text-gray-600">{{ ($attachmentMedia['has_media'] ?? false) ? 'Ganti file lampiran' : 'Klik untuk memilih file' }}</span>
                        <span class="text-xs text-gray-400">PDF, JPG, PNG, DOC — maks. 10 MB per file</span>
                        <input type="file" name="lampiran[]" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" class="sr-only">
                    </label>
                    <p class="mt-2 text-xs text-gray-500">File disimpan di server dan dapat dibuka dosen saat persetujuan proyek.</p>
                    @error('lampiran')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('lampiran.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                    <a href="{{ $classContext ? route('classes.show', $classContext['id']) : route('my-project') }}"
                       class="text-center bg-gray-200 text-gray-700 px-8 py-2.5 rounded-full font-bold text-sm hover:bg-gray-300 transition">
                        Batal
                    </a>
                    @if($isEdit && $editMode !== 'draft')
                    <button type="submit" name="action" value="submit_revision"
                            class="bg-slate-900 text-white px-8 py-2.5 rounded-full font-bold text-sm hover:bg-slate-800 transition shadow-lg">
                        <i class="fas fa-paper-plane mr-1"></i> Ajukan Perbaikan ke Dosen
                    </button>
                    @else
                    <button type="submit" name="action" value="draft"
                            class="bg-white border-2 border-blue-600 text-blue-600 px-8 py-2.5 rounded-full font-bold text-sm hover:bg-blue-50 transition">
                        Simpan ke Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                            class="bg-slate-900 text-white px-8 py-2.5 rounded-full font-bold text-sm hover:bg-slate-800 transition shadow-lg">
                        <i class="fas fa-paper-plane mr-1"></i> Ajukan ke Dosen
                    </button>
                    @endif
                </div>
            </form>
        </div>
</div>
@endsection
