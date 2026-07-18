@extends('layouts.app')

@section('title', 'Penilaian Proyek - PjBL')

@section('content')
<div class="w-full space-y-6" x-data="{ addGroup: false, addIndividual: false, revisiModal: false }">

    <a href="{{ route('dosen.proyek-mahasiswa.show', $project['id']) }}" class="text-blue-600 text-xs font-bold hover:underline mb-4 inline-block">
        &larr; Kembali ke detail proyek
    </a>

    @include('partials.flash-messages')

    @if($evaluation)
    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-4">
        <div><p class="font-bold text-slate-800">Status penilaian: {{ ($evaluation->publication_status ?? 'draft') === 'published' ? 'Published' : 'Draft' }}</p><p class="text-xs text-slate-500">Mahasiswa hanya dapat melihat nilai saat status Published.</p></div>
        <form method="POST" action="{{ route(($evaluation->publication_status ?? 'draft') === 'published' ? 'dosen.penilaian.unpublish' : 'dosen.penilaian.publish', $project['id']) }}">@csrf
            <button class="rounded-xl px-5 py-2.5 text-sm font-bold text-white {{ ($evaluation->publication_status ?? 'draft') === 'published' ? 'bg-slate-600' : 'bg-emerald-600' }}">{{ ($evaluation->publication_status ?? 'draft') === 'published' ? 'Unpublish Nilai' : 'Publish Nilai' }}</button>
        </form>
    </div>
    @endif

    @if($reflections->isNotEmpty())
    <div class="rounded-2xl border border-slate-200 bg-white p-6"><h3 class="font-bold text-slate-900">Refleksi Mahasiswa</h3><div class="mt-4 space-y-3">@foreach($reflections as $reflection)<details class="rounded-xl bg-slate-50 p-4"><summary class="cursor-pointer font-semibold">{{ $reflection->full_name ?? 'Mahasiswa' }} — {{ $reflection->status }}</summary><div class="mt-3 space-y-2 text-sm">@foreach(json_decode($reflection->answers, true) ?? [] as $key => $answer)<p><b>{{ str_replace('_', ' ', $key) }}:</b> {{ $answer }}</p>@endforeach</div></details>@endforeach</div></div>
    @endif

    @unless($tasksFinalized)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
        <p class="text-sm font-bold text-amber-900">Tim belum mengirim finalisasi proyek</p>
        <p class="text-sm text-amber-800 mt-1">
            @if($projectStatus === 'pending_final_revision')
                Anda sudah meminta revisi. Tim sedang memperbaiki dan belum mengirim ulang finalisasinya.
            @elseif($progress['total'] === 0)
                Mahasiswa belum menyusun satu pun tugas pada proyek ini.
            @else
                Baru {{ $progress['done'] }} dari {{ $progress['total'] }} tugas yang selesai ({{ $progress['percent'] }}%).
                @if($pendingApproval > 0)
                    Ada {{ $pendingApproval }} tugas yang menunggu persetujuan Anda di halaman Pelaksanaan.
                @endif
            @endif
            Anda tetap dapat menilai sekarang, tetapi sebaiknya tunggu tim menekan tombol Submit Finalisasi Proyek.
        </p>
    </div>
    @endunless

    {{-- BERKAS FINALISASI DARI TIM --}}
    @if($finalSubmission)
    <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                    <i class="fas fa-box-archive mr-1 text-blue-600"></i>Berkas Finalisasi Proyek
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Dikirim {{ $finalSubmission->submitted_at?->format('d M Y H:i') }}
                    @if($finalHistory->count() > 1)
                        &middot; pengiriman ke-{{ $finalHistory->count() }}
                    @endif
                </p>
            </div>

            @php
                $badge = match ($finalSubmission->status) {
                    'accepted' => ['Diterima & dinilai', 'bg-emerald-100 text-emerald-700'],
                    'revision_requested' => ['Diminta revisi', 'bg-amber-100 text-amber-700'],
                    default => ['Menunggu penilaian Anda', 'bg-blue-100 text-blue-700'],
                };
            @endphp
            <span class="rounded-full px-4 py-1.5 text-[11px] font-bold {{ $badge[1] }}">{{ $badge[0] }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            @if($finalSubmission->report_url)
            <a href="{{ $finalSubmission->report_url }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 hover:bg-blue-100 transition">
                <i class="fas {{ $finalSubmission->report_type === 'link' ? 'fa-link' : 'fa-file-pdf' }} text-blue-600 text-lg"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase text-blue-500">Laporan Akhir</p>
                    <p class="text-xs font-bold text-blue-800 truncate">
                        {{ $finalSubmission->report_type === 'link' ? 'Buka tautan laporan' : $finalSubmission->report_name }}
                    </p>
                </div>
            </a>
            @endif

            @if($finalSubmission->presentation_link)
            <a href="{{ $finalSubmission->presentation_link }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100 transition">
                <i class="fas fa-display text-slate-500 text-lg"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase text-slate-400">Presentasi</p>
                    <p class="text-xs font-bold text-slate-700 truncate">Buka tautan</p>
                </div>
            </a>
            @endif

            @if($finalSubmission->repo_link)
            <a href="{{ $finalSubmission->repo_link }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100 transition">
                <i class="fas fa-code-branch text-slate-500 text-lg"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase text-slate-400">Repo / Demo</p>
                    <p class="text-xs font-bold text-slate-700 truncate">Buka tautan</p>
                </div>
            </a>
            @endif
        </div>

        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
            <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Ringkasan Hasil dari Tim</p>
            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $finalSubmission->summary }}</p>
        </div>

        @if($finalSubmission->lecturer_note)
        <div class="mt-3 rounded-2xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-[10px] font-bold uppercase text-amber-600 mb-1">Catatan Revisi Anda</p>
            <p class="text-sm text-amber-800 whitespace-pre-line">{{ $finalSubmission->lecturer_note }}</p>
        </div>
        @endif

        @if($projectStatus === 'pending_final_review')
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
            <p class="text-xs text-slate-500">
                Isi penilaian di bawah lalu simpan untuk menutup proyek, atau minta tim memperbaiki finalisasinya.
            </p>
            <button type="button" @click="revisiModal = true"
                    class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-2.5 text-xs font-bold text-amber-700 hover:bg-amber-100 transition">
                <i class="fas fa-rotate-left mr-1"></i>Minta Revisi
            </button>
        </div>
        @endif

        {{-- Riwayat pengiriman sebelumnya --}}
        @if($finalHistory->count() > 1)
        <details class="mt-5 border-t border-slate-100 pt-4">
            <summary class="cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-700">
                Riwayat pengiriman ({{ $finalHistory->count() }})
            </summary>
            <ul class="mt-3 space-y-2">
                @foreach($finalHistory as $item)
                <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-2.5">
                    <span class="text-xs text-slate-600">
                        {{ $item->submitted_at?->format('d M Y H:i') }}
                        @if($item->report_url)
                            &middot; <a href="{{ $item->report_url }}" target="_blank" rel="noopener" class="font-bold text-blue-600 hover:underline">laporan</a>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold uppercase text-slate-400">{{ $item->status }}</span>
                </li>
                @endforeach
            </ul>
        </details>
        @endif
    </div>

    {{-- Modal minta revisi --}}
    <div x-show="revisiModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl" @click.outside="revisiModal = false">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Minta Revisi Finalisasi</h3>
            <p class="text-xs text-slate-500 mb-4">
                Proyek akan dibuka kembali agar tim dapat memperbaiki dan mengirim ulang finalisasinya.
            </p>
            <form method="POST" action="{{ route('dosen.finalisasi.revisi', $project['id']) }}">
                @csrf
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan perbaikan <span class="text-red-500">*</span></label>
                <textarea name="note" rows="4" required maxlength="1000"
                          placeholder="Bagian mana yang perlu diperbaiki tim..."
                          class="w-full border rounded-xl p-3 text-sm outline-none focus:border-amber-400 resize-none mb-5"></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="revisiModal = false" class="px-5 py-2 bg-gray-200 rounded-xl text-sm font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 text-white rounded-xl text-sm font-bold">Kirim Permintaan Revisi</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <p class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-2">Penilaian Proyek</p>
        <h1 class="text-2xl font-bold text-slate-900">{{ $project['name'] }}</h1>
        <p class="text-sm text-slate-500 mt-2">{{ $project['group_name'] ?? '-' }} &middot; {{ $project['course_name'] ?? '-' }}</p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase">Progres Tugas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $progress['percent'] }}%</p>
                <p class="text-xs text-slate-500">{{ $progress['done'] }} dari {{ $progress['total'] }} selesai</p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase">Penilaian Antar Anggota</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $peer['group_average'] ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $peer['submitted'] }} dari {{ $peer['total'] }} mahasiswa mengisi</p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase">Status Penilaian</p>
                <p class="mt-1 text-lg font-bold {{ $evaluation ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $evaluation ? 'Sudah dinilai' : 'Belum dinilai' }}
                </p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-[11px] font-bold text-slate-500 uppercase">Terakhir Dinilai</p>
                <p class="mt-1 text-sm font-bold text-slate-900">
                    {{ $evaluation?->evaluated_at ? \Carbon\Carbon::parse($evaluation->evaluated_at)->format('d M Y H:i') : '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Rekap penilaian antar anggota dari mahasiswa --}}
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Rekap Penilaian Antar Anggota</h2>

        @if($peer['submitted'] === 0)
            <p class="text-sm text-slate-500 italic">Belum ada mahasiswa yang mengisi penilaian kelompok.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($peer['members'] as $member)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-800">{{ $member['name'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700">{{ $member['average'] ?? '-' }}</p>
                    <p class="text-xs text-slate-500">dinilai {{ $member['voters'] }} rekan</p>
                </div>
                @endforeach
            </div>

            @if(count($peer['reflections']) > 0)
            <div class="mt-5 space-y-2">
                <p class="text-xs font-bold text-slate-500 uppercase">Refleksi Mahasiswa</p>
                @foreach($peer['reflections'] as $reflection)
                <p class="text-sm text-slate-600 italic border-l-4 border-slate-200 pl-3">"{{ $reflection }}"</p>
                @endforeach
            </div>
            @endif
        @endif
    </div>

    <form method="POST" action="{{ route('dosen.penilaian.store', $project['id']) }}" class="space-y-6">
        @csrf

        {{-- Penilaian kelompok --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Penilaian Kelompok</h2>
                    <p class="text-sm text-slate-500">Isi nilai tiap komponen (0-100). Nilai akhir kelompok diisi manual di bawah.</p>
                </div>
                <button type="button" @click="addGroup = true"
                        class="shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 transition">
                    <i class="fas fa-plus"></i> Tambah komposisi
                </button>
            </div>

            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Komponen</th>
                            <th class="px-4 py-3 text-left font-semibold w-24">Bobot</th>
                            <th class="px-4 py-3 text-right font-semibold w-32">Nilai</th>
                            <th class="px-4 py-3 text-center font-semibold w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($components as $key => $component)
                        <tr class="border-t border-slate-200">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $component['label'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $component['weight'] !== null ? $component['weight'].'%' : '-' }}</td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" min="0" max="100" name="components[{{ $key }}]"
                                       value="{{ old('components.'.$key, $evaluation->components[$key] ?? '') }}"
                                       class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-right outline-none focus:border-blue-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                {{-- form dihapus ada di luar form penilaian (form bersarang tidak valid) --}}
                                <button type="submit" form="hapus-komposisi-{{ $component['id'] }}"
                                        onclick="return confirm('Hapus komponen {{ $component['label'] }}?')"
                                        class="text-red-500 hover:text-red-700 transition" title="Hapus komponen">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400 italic">
                                Belum ada komponen. Tambahkan lewat tombol "Tambah komposisi".
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 md:grid-cols-[200px_1fr] mt-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nilai Akhir Kelompok *</label>
                    <input type="number" min="0" max="100" name="group_score" required
                           value="{{ old('group_score', $evaluation->group_score ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-lg font-bold outline-none focus:border-blue-500">
                    @error('group_score')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Catatan untuk Kelompok</label>
                    <textarea name="note" rows="3" maxlength="1000"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-blue-500 text-sm"
                              placeholder="Catatan, apresiasi, atau saran perbaikan...">{{ old('note', $evaluation->note ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Penilaian individu --}}
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Penilaian Individu</h2>
                    <p class="text-sm text-slate-500">Aktivitas nyata tiap mahasiswa ditampilkan sebagai bahan pertimbangan.</p>
                </div>
                <button type="button" @click="addIndividual = true"
                        class="shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 transition">
                    <i class="fas fa-plus"></i> Tambah komposisi
                </button>
            </div>

            {{-- Daftar kriteria individu yang berlaku untuk semua mahasiswa --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-bold uppercase text-slate-400">Kriteria:</span>
                @forelse($criteria as $key => $criterion)
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                    {{ $criterion['label'] }}
                    <button type="submit" form="hapus-komposisi-{{ $criterion['id'] }}"
                            onclick="return confirm('Hapus kriteria {{ $criterion['label'] }}?')"
                            class="text-red-500 hover:text-red-700 transition" title="Hapus kriteria">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                </span>
                @empty
                <span class="text-xs text-slate-400 italic">Belum ada kriteria individu.</span>
                @endforelse
            </div>

            @foreach($students as $student)
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm">
                            {{ $student['initials'] }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">{{ $student['name'] }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $student['activity']['tasks_done'] }}/{{ $student['activity']['tasks_assigned'] }} tugas selesai &middot;
                                {{ $student['activity']['comments'] }} komentar &middot;
                                {{ $student['activity']['problems'] }} usulan masalah &middot;
                                tepat waktu {{ $student['activity']['on_time_percent'] }}%
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Nilai Akhir *</label>
                        <input type="number" min="0" max="100" required
                               name="students[{{ $student['id'] }}][score]"
                               value="{{ old('students.'.$student['id'].'.score', $student['score']) }}"
                               class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-right font-bold outline-none focus:border-blue-500">
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5 mb-4">
                    @foreach($criteria as $key => $criterion)
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">{{ $criterion['label'] }}</label>
                        <input type="number" min="0" max="100"
                               name="students[{{ $student['id'] }}][criteria][{{ $key }}]"
                               value="{{ old('students.'.$student['id'].'.criteria.'.$key, $student['criteria'][$key] ?? '') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500">
                    </div>
                    @endforeach
                </div>

                <textarea name="students[{{ $student['id'] }}][feedback]" rows="2" maxlength="1000"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
                          placeholder="Umpan balik untuk {{ $student['name'] }}...">{{ old('students.'.$student['id'].'.feedback', $student['feedback']) }}</textarea>
            </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('dosen.proyek-mahasiswa.show', $project['id']) }}"
               class="rounded-full bg-slate-200 px-8 py-3 text-xs font-bold text-slate-700 hover:bg-slate-300 transition">Batal</a>
            <button type="submit"
                    class="rounded-full bg-blue-600 px-8 py-3 text-xs font-bold text-white hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                Simpan &amp; Kirim ke Mahasiswa
            </button>
        </div>
    </form>

    {{-- Form hapus komposisi: sengaja di luar form penilaian, dipanggil lewat atribut form="...".
         Digabung per baris (bukan union array) karena key komponen & kriteria bisa sama. --}}
    @foreach(array_merge(array_values($components), array_values($criteria)) as $item)
    <form id="hapus-komposisi-{{ $item['id'] }}" method="POST"
          action="{{ route('dosen.penilaian.komposisi.hapus', $project['id']) }}" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="component_id" value="{{ $item['id'] }}">
    </form>
    @endforeach

    {{-- Modal: tambah komponen penilaian kelompok --}}
    <div x-show="addGroup" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(15,23,42,0.45);"
         @keydown.escape.window="addGroup = false">
        <div @click.outside="addGroup = false"
             class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900">Tambah Komponen Kelompok</h3>
            <p class="text-sm text-slate-400 mt-1">Komponen baru akan muncul di tabel penilaian kelompok.</p>

            <form method="POST" action="{{ route('dosen.penilaian.komposisi.tambah', $project['id']) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="type" value="group">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Komponen *</label>
                    <input type="text" name="label" required maxlength="100"
                           placeholder="mis. Inovasi Solusi"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Bobot (%)</label>
                    <input type="number" name="weight" min="0" max="100"
                           placeholder="mis. 10"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:bg-white transition">
                    <p class="text-xs text-slate-400 mt-1">Boleh dikosongkan bila komponen ini tidak berbobot.</p>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="addGroup = false"
                            class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: tambah kriteria penilaian individu --}}
    <div x-show="addIndividual" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(15,23,42,0.45);"
         @keydown.escape.window="addIndividual = false">
        <div @click.outside="addIndividual = false"
             class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900">Tambah Kriteria Individu</h3>
            <p class="text-sm text-slate-400 mt-1">Kriteria baru berlaku untuk semua mahasiswa di proyek ini.</p>

            <form method="POST" action="{{ route('dosen.penilaian.komposisi.tambah', $project['id']) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="type" value="individual">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Kriteria *</label>
                    <input type="text" name="label" required maxlength="100"
                           placeholder="mis. Inisiatif"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:bg-white transition">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="addIndividual = false"
                            class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
