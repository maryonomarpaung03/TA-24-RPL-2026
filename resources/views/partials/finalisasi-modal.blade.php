{{-- Modal pra-finalisasi proyek, dirender dari bar aksi tahap Assessment & Reflection.
     Butuh state Alpine `finalModal` di ancestor; $finalReadiness dan $selected_project
     disiapkan AppServiceProvider, jadi modal ini tidak terikat ke satu controller. --}}
@php
    $id = $selected_project['id'];
    $readiness = $finalReadiness;
@endphp
<div x-show="finalModal" x-cloak
     class="fixed inset-0 z-[110] flex items-start justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4 py-10"
     @keydown.escape.window="finalModal = false">

    <div @click.outside="finalModal = false"
         x-data="{
            reportType: 'file',
            confirmComments: false,
            confirmData: false,
            confirmFinal: false,
            reportFile: null,
            reportLink: '',
            summary: '',
            get reportReady() {
                return this.reportType === 'file' ? !!this.reportFile : this.reportLink.trim().length > 8;
            },
            get canSubmit() {
                return {{ $readiness['passed'] ? 'true' : 'false' }}
                    && this.reportReady
                    && this.summary.trim().length >= 20
                    && this.confirmComments && this.confirmData && this.confirmFinal;
            }
         }"
         class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl p-8 my-auto">

        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Submit Finalisasi Proyek</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Periksa daftar berikut sebelum mengirim. Setelah dikirim, papan tugas terkunci
                    sampai dosen selesai menilai.
                </p>
            </div>
            <button type="button" @click="finalModal = false" class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- A. Prasyarat yang dicek sistem --}}
        <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-5 mb-6">
            <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700 mb-3">
                <i class="fas {{ $readiness['passed'] ? 'fa-circle-check' : 'fa-triangle-exclamation' }} mr-1"></i>
                Prasyarat sistem
            </p>

            <ul class="space-y-2.5">
                @foreach($readiness['items'] as $item)
                <li class="flex items-start gap-3">
                    <i class="fas {{ $item['passed'] ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-red-400' }} mt-0.5"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold {{ $item['passed'] ? 'text-gray-800' : 'text-gray-500' }}">{{ $item['label'] }}</p>
                        <p class="text-xs {{ $item['passed'] ? 'text-gray-500' : 'text-red-500' }}">{{ $item['detail'] }}</p>
                    </div>
                </li>
                @endforeach
            </ul>

            @unless($readiness['passed'])
            <p class="mt-4 text-xs font-bold text-blue-800">
                Lengkapi dulu poin bertanda merah di atas. Tombol submit akan aktif setelah semuanya hijau.
            </p>
            @endunless
        </div>

        <form method="POST" action="{{ route('finalisasi.submit', $id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- B. Laporan akhir: unggah berkas ATAU tautan --}}
            <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Laporan Akhir <span class="text-red-500">*</span>
                    </label>

                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer rounded-xl border px-4 py-3 text-sm font-semibold transition"
                               :class="reportType === 'file' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-500'">
                            <input type="radio" name="report_type" value="file" x-model="reportType" class="hidden">
                            <i class="fas fa-file-arrow-up mr-1.5"></i>Unggah berkas
                        </label>
                        <label class="flex-1 cursor-pointer rounded-xl border px-4 py-3 text-sm font-semibold transition"
                               :class="reportType === 'link' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-500'">
                            <input type="radio" name="report_type" value="link" x-model="reportType" class="hidden">
                            <i class="fas fa-link mr-1.5"></i>Berikan link
                        </label>
                    </div>
                </div>

                <div x-show="reportType === 'file'" x-cloak>
                    <input type="file" name="report" accept=".pdf,.doc,.docx"
                           :disabled="reportType !== 'file'"
                           @change="reportFile = $event.target.files[0] || null"
                           class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-blue-700">
                    <p class="mt-1 text-[11px] text-gray-400">PDF atau DOC/DOCX, maks. 10 MB.</p>
                </div>

                <div x-show="reportType === 'link'" x-cloak>
                    <input type="url" name="report_link" x-model="reportLink"
                           :disabled="reportType !== 'link'"
                           placeholder="https://drive.google.com/..."
                           class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500">
                    <p class="mt-1 text-[11px] text-gray-400">Tautan Google Drive, OneDrive, atau repositori yang dapat diakses dosen.</p>
                </div>
            </div>

            {{-- Pelengkap (opsional) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Link Presentasi <span class="font-normal normal-case text-gray-400">(opsional)</span>
                    </label>
                    <input type="url" name="presentation_link" placeholder="https://..."
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Link Repo / Demo <span class="font-normal normal-case text-gray-400">(opsional)</span>
                    </label>
                    <input type="url" name="repo_link" placeholder="https://github.com/..."
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                    Ringkasan Hasil Proyek <span class="text-red-500">*</span>
                </label>
                <textarea name="summary" rows="4" x-model="summary" maxlength="2000"
                          placeholder="Apa yang berhasil tim capai, kendala utama, dan hasil akhirnya..."
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none resize-none focus:border-blue-500"></textarea>
                <p class="mt-1 text-[11px] text-gray-400">
                    Minimal 20 karakter &middot; <span x-text="summary.trim().length"></span>/2000
                </p>
            </div>

            {{-- C. Pernyataan tim --}}
            <div class="rounded-2xl border border-gray-200 p-5 space-y-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Pernyataan tim</p>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirm_comments" value="1" x-model="confirmComments"
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Kami sudah membaca seluruh komentar dosen pada tiap tugas.</span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirm_data" value="1" x-model="confirmData"
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Data dan berkas yang kami unggah sudah benar dan final.</span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirm_final" value="1" x-model="confirmFinal"
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">
                        Kami menyatakan proyek ini <span class="font-bold">final dan siap dinilai</span>.
                        Papan tugas akan terkunci setelah dikirim.
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" @click="finalModal = false"
                        class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">
                    Batal
                </button>
                <button type="submit" :disabled="!canSubmit"
                        class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold transition disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 hover:bg-blue-700">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim Finalisasi ke Dosen
                </button>
            </div>
        </form>
    </div>
</div>
