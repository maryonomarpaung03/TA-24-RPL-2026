@php
    $projectId = $selected_project['id'];
@endphp
<div class="space-y-6" x-data="problemBoardApp({
    projectId: @js($projectId),
    showForm: false,
    isPm: @js($isPm ?? false),
    participantCount: @js($participantCount ?? 1),
    votersCount: @js($votersCount ?? 0),
    board: @js($problemBoard ?? ['ide' => [], 'voting' => [], 'diajukan' => [], 'perbaiki' => [], 'selesai' => []]),
    comments: @js($problemComments ?? []),
    currentUserId: @js($currentUserId ?? 0),
    routes: {
        store: @js(route('problem.store', $projectId)),
        update: @js(route('problem.update', $projectId)),
        delete: @js(route('problem.delete', $projectId)),
        propose: @js(route('problem.propose-voting', $projectId)),
        vote: @js(route('problem.vote', $projectId)),
        comment: @js(route('problem.comment', $projectId)),
        discuss: @js(route('problem.discuss', $projectId)),
        submit: @js(route('problem.submit-lecturer', $projectId)),
        resubmit: @js(route('problem.resubmit', $projectId)),
    },
    csrf: @js(csrf_token()),
})">
    <div class="bg-white rounded-3xl px-6 py-5 shadow-sm border border-slate-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-[0.3em] text-blue-500 font-semibold mb-1.5">Problem Identification</p>
                <h2 class="text-2xl font-bold text-slate-900 truncate">{{ $selected_project['name'] }}</h2>
                <p class="mt-1.5 text-sm text-slate-400 line-clamp-2">{{ $selected_project['description'] }}</p>
            </div>
        </div>
    </div>

    <div x-show="flash" x-cloak class="rounded-2xl border px-4 py-3 text-sm"
         :class="flashType === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800'">
        <span x-text="flash"></span>
    </div>

    @if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($selected_project['is_pending_revision'] ?? false)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Perubahan proyek sedang menunggu persetujuan dosen. Tim tetap dapat mengakses dan melanjutkan PjBL.
    </div>
    @endif

    {{-- Modal: Tambah Ide --}}
    <div x-show="showForm" x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 sm:p-6"
         style="background: rgba(15,23,42,0.45); backdrop-filter: blur(4px);"
         @keydown.escape.window="showForm = false">
        <div @click.stop
             x-show="showForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-[2rem] border border-slate-200 shadow-2xl w-full max-w-2xl">
            <div class="flex items-center justify-between px-8 pt-7 pb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Tambah Ide Masalah</h3>
                    <p class="text-sm text-slate-400 mt-0.5">Ajukan ide masalah baru ke board tim.</p>
                </div>
                <button type="button" @click="showForm = false"
                        class="h-9 w-9 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>
            <div class="px-8 py-6 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-slate-500">Judul Masalah</label>
                    <input x-model="form.title" type="text"
                           class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-blue-400 focus:bg-white transition"
                           placeholder="Contoh: Input data absensi masih manual">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-500">Deskripsi</label>
                    <textarea x-model="form.description" rows="3"
                              class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-blue-400 focus:bg-white transition resize-none"
                              placeholder="Detail masalah / konteks singkat..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-500">Kategori</label>
                        <select x-model="form.category"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-blue-400 focus:bg-white transition">
                            <option>Teknik</option>
                            <option>Diskusi</option>
                            <option>Etika</option>
                            <option>Kebutuhan Proyek</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-500">Prioritas</label>
                        <select x-model="form.priority"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-blue-400 focus:bg-white transition">
                            <option>Tinggi</option>
                            <option>Sedang</option>
                            <option>Rendah</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-slate-500">Attachment (opsional)</label>
                        <button type="button" @click="addAttachment()"
                                class="flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 transition">
                            <i class="fas fa-plus text-xs"></i> Tambah
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(att, index) in form.attachments" :key="index">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex gap-1.5">
                                        <button type="button"
                                                @click="att.type = 'link'; att.value = ''; att.preview = null"
                                                :class="att.type === 'link' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300'"
                                                class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold transition">
                                            <i class="fas fa-link text-xs"></i> Link
                                        </button>
                                        <button type="button"
                                                @click="att.type = 'file'; att.value = ''; att.preview = null"
                                                :class="att.type === 'file' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300'"
                                                class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold transition">
                                            <i class="fas fa-file text-xs"></i> File
                                        </button>
                                        <button type="button"
                                                @click="att.type = 'gambar'; att.value = ''; att.preview = null"
                                                :class="att.type === 'gambar' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300'"
                                                class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold transition">
                                            <i class="fas fa-image text-xs"></i> Gambar
                                        </button>
                                    </div>
                                    <button type="button" x-show="form.attachments.length > 1"
                                            @click="removeAttachment(index)"
                                            class="h-7 w-7 flex items-center justify-center rounded-full text-slate-400 hover:bg-red-100 hover:text-red-500 transition text-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div x-show="att.type === 'link'">
                                    <input x-model="att.value" type="url"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-base outline-none focus:border-blue-400 transition"
                                           placeholder="https://drive.google.com/... atau link lainnya">
                                </div>
                                <div x-show="att.type === 'file'">
                                    <label class="flex items-center gap-2 w-full rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2.5 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
                                        <i class="fas fa-upload text-slate-400 text-sm"></i>
                                        <span class="text-base text-slate-500 truncate" x-text="att.value ? att.value : 'Pilih file...'"></span>
                                        <input type="file" class="hidden"
                                               @change="att.value = $event.target.files[0]?.name ?? ''">
                                    </label>
                                </div>
                                <div x-show="att.type === 'gambar'" class="space-y-2">
                                    <label class="flex items-center gap-2 w-full rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2.5 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition">
                                        <i class="fas fa-image text-slate-400 text-sm"></i>
                                        <span class="text-base text-slate-500 truncate" x-text="att.value ? att.value : 'Pilih gambar...'"></span>
                                        <input type="file" accept="image/*" class="hidden"
                                               @change="handleAttachmentImage($event, index)">
                                    </label>
                                    <template x-if="att.preview">
                                        <img :src="att.preview" class="w-full max-h-28 object-cover rounded-xl border border-slate-200">
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 px-8 pb-7">
                <button type="button" @click="addIdeaCard()" :disabled="loading || !form.title.trim()"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-5 py-3.5 text-base font-semibold text-white hover:bg-blue-700 transition disabled:opacity-50">
                    <i class="fas fa-plus"></i>
                    <span x-text="loading ? 'Menyimpan...' : 'Tambah ke Board'"></span>
                </button>
                <button type="button" @click="showForm = false"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-3.5 text-base font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[2fr_1fr] gap-6 xl:items-stretch">
        <div class="min-w-0 flex flex-col">
            <div class="bg-white rounded-[2rem] border border-slate-200 p-5 shadow-sm flex-1 flex flex-col">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-xs uppercase tracking-[0.3em] text-gray-400 font-semibold">Alur Identifikasi Masalah</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ide → Voting → Diajukan → Perbaiki → Selesai</p>
                    </div>
                    <button type="button" @click="showForm = true" class="shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                        <i class="fas fa-plus"></i> Tambah ide
                    </button>
                </div>

                {{-- Board: horizontal scroll --}}
                <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50 p-3 flex-1 min-h-0">
                    <div class="flex gap-3 items-stretch h-full" style="min-width: max-content;">

                        {{-- Ide Masalah --}}
                        <div id="board-idea" class="w-72 shrink-0 rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col">
                            <div class="rounded-t-2xl bg-slate-100 border-b border-slate-200 px-3 py-2.5 shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-slate-400 shrink-0"></span>
                                        <p class="text-xs font-bold text-slate-700">Ide Masalah</p>
                                    </div>
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="board.ide.length"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Tambah dan ajukan ke voting</p>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                <p x-show="board.ide.length === 0" class="text-xs text-slate-400 italic py-8 text-center">Belum ada ide masalah.</p>
                                <template x-for="card in board.ide" :key="'ide-' + card.id">
                                    <div :data-id="card.id" class="problem-card rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        {{-- Mode tampil --}}
                                        <template x-if="!card._editing">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="card.title"></p>
                                                <p class="text-xs text-slate-400 mt-1.5" x-text="card.author_name"></p>
                                                <div class="flex flex-wrap gap-1 mt-1.5">
                                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600" x-text="card.category"></span>
                                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600" x-text="card.priority"></span>
                                                </div>
                                                <p class="text-xs text-slate-500 mt-1.5 line-clamp-2" x-text="card.description"></p>
                                                <button type="button" @click="proposeVoting(card)" class="mt-2.5 w-full rounded-lg border border-blue-200 bg-blue-50 px-2 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                                                    Ajukan untuk voting
                                                </button>
                                                <div x-show="card.created_by === currentUserId || isPm" class="mt-2 flex items-center gap-3 border-t border-slate-200 pt-2 text-[11px]">
                                                    <button type="button" @click="startEditIdea(card)" class="font-semibold text-slate-500 hover:text-blue-600 transition">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>
                                                    <button type="button" @click="deleteIdea(card)" class="font-semibold text-slate-500 hover:text-red-600 transition">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Mode edit --}}
                                        <template x-if="card._editing">
                                            <div class="space-y-2">
                                                <input x-model="card._editTitle" type="text" placeholder="Judul masalah"
                                                       class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400">
                                                <textarea x-model="card._editDesc" rows="2" placeholder="Deskripsi"
                                                          class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400 resize-none"></textarea>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <select x-model="card._editCategory" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400">
                                                        <option>Teknik</option>
                                                        <option>Diskusi</option>
                                                        <option>Etika</option>
                                                        <option>Kebutuhan Proyek</option>
                                                    </select>
                                                    <select x-model="card._editPriority" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400">
                                                        <option>Tinggi</option>
                                                        <option>Sedang</option>
                                                        <option>Rendah</option>
                                                    </select>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="button" @click="saveIdea(card)" :disabled="loading"
                                                            class="flex-1 rounded-lg bg-blue-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition disabled:opacity-50">
                                                        Simpan
                                                    </button>
                                                    <button type="button" @click="card._editing = false"
                                                            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                                                        Batal
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Voting --}}
                        <div id="board-voting" class="w-72 shrink-0 rounded-2xl border border-blue-200 bg-white shadow-sm flex flex-col">
                            <div class="rounded-t-2xl bg-blue-50 border-b border-blue-100 px-3 py-2.5 shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-blue-500 shrink-0"></span>
                                        <p class="text-xs font-bold text-blue-700">Voting</p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                          :class="votingOpen ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                                          x-text="votingOpen ? 'Dibuka' : 'Ditutup'"></span>
                                </div>
                                <p class="text-[10px] text-blue-500 mt-1 font-medium" x-text="votersCount + ' / ' + participantCount + ' anggota sudah vote'"></p>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                <p x-show="board.voting.length === 0" class="text-xs text-slate-400 italic py-8 text-center">Belum ada yang divoting.</p>
                                <template x-for="card in sortedVotingCards()" :key="'vote-' + card.id">
                                    <div :data-id="card.id" class="problem-card rounded-xl border p-3"
                                         :class="card.is_my_vote ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-slate-50'">
                                        <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="card.title"></p>
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <i class="fas fa-thumbs-up text-[10px] text-blue-500"></i>
                                            <p class="text-xs text-slate-500">Vote: <span class="font-bold text-blue-600" x-text="card.votes ?? 0"></span></p>
                                        </div>
                                        <button type="button" @click="castVote(card)" :disabled="!votingOpen || loading"
                                                class="mt-2 w-full rounded-lg px-2 py-2 text-xs font-semibold text-white transition disabled:opacity-40"
                                                :class="card.is_my_vote ? 'bg-slate-600 hover:bg-slate-700' : 'bg-blue-600 hover:bg-blue-700'"
                                                x-text="card.is_my_vote ? 'Batalkan vote' : 'Vote'"></button>
                                        <div class="mt-2.5 border-t border-slate-200 pt-2.5 space-y-2">
                                            {{-- Daftar komentar yang sudah ada --}}
                                            <div class="space-y-1.5 max-h-36 overflow-y-auto pr-0.5">
                                                <template x-for="vc in comments.filter(cm => cm.problem_id === card.id)" :key="'vc-' + vc.id">
                                                    <div class="rounded-lg bg-white border border-slate-100 px-2.5 py-2">
                                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                                            <span class="text-[10px] font-semibold text-slate-700" x-text="vc.from"></span>
                                                            <span class="text-[10px] text-slate-400 shrink-0" x-text="vc.time"></span>
                                                        </div>
                                                        <p class="text-xs text-slate-600 leading-relaxed" x-text="vc.text"></p>
                                                    </div>
                                                </template>
                                            </div>
                                            {{-- Input komentar baru --}}
                                            <textarea x-model="cardDrafts[card.id]" rows="2"
                                                      class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400 resize-none"
                                                      placeholder="Tulis komentar sebagai bahan pertimbangan dosen..."></textarea>
                                            <button type="button" @click="postComment(card)" :disabled="loading || !votingOpen"
                                                    class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition disabled:opacity-40">
                                                <i class="fas fa-comment-dots mr-1"></i> Kirim komentar
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="isPm && board.voting.length > 0" class="p-2.5 border-t border-blue-100 shrink-0">
                                <button type="button" @click="submitToLecturer()" :disabled="loading"
                                        class="w-full rounded-xl bg-slate-900 px-2 py-2.5 text-xs font-semibold text-white hover:bg-slate-800 transition disabled:opacity-50">
                                    <i class="fas fa-paper-plane mr-1.5"></i> Ajukan ke Dosen
                                </button>
                                <p class="text-[10px] text-slate-400 mt-1 text-center">Otomatis saat semua anggota vote</p>
                            </div>
                        </div>

                        {{-- Diajukan --}}
                        <div id="board-submitted" class="w-72 shrink-0 rounded-2xl border border-amber-200 bg-white shadow-sm flex flex-col">
                            <div class="rounded-t-2xl bg-amber-50 border-b border-amber-100 px-3 py-2.5 shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                                        <p class="text-xs font-bold text-amber-700">Diajukan</p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-600" x-text="board.diajukan.length"></span>
                                </div>
                                <p class="text-[10px] text-amber-600 mt-1">Menunggu review dosen</p>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                <p x-show="board.diajukan.length === 0" class="text-xs text-slate-400 italic py-8 text-center">Belum ada pengajuan.</p>
                                <template x-for="card in board.diajukan" :key="'aju-' + card.id">
                                    <div :data-id="card.id" class="problem-card rounded-xl border border-amber-200 bg-amber-50 p-3">
                                        <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="card.title"></p>
                                        <div class="flex items-center gap-1.5 mt-2">
                                            <i class="fas fa-hourglass-half text-[10px] text-amber-500"></i>
                                            <p class="text-xs text-amber-700 font-medium" x-text="card.status_label || 'Menunggu Dosen'"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Perbaiki --}}
                        <div id="board-revision" class="w-72 shrink-0 rounded-2xl border border-red-200 bg-white shadow-sm flex flex-col">
                            <div class="rounded-t-2xl bg-red-50 border-b border-red-100 px-3 py-2.5 shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-red-500 shrink-0"></span>
                                        <p class="text-xs font-bold text-red-700">Perbaiki</p>
                                    </div>
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-600" x-text="board.perbaiki.length"></span>
                                </div>
                                <p class="text-[10px] text-red-500 mt-1">Perlu direvisi</p>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                <p x-show="board.perbaiki.length === 0" class="text-xs text-slate-400 italic py-8 text-center">Tidak ada revisi.</p>
                                <template x-for="card in board.perbaiki" :key="'fix-' + card.id">
                                    <div :data-id="card.id" class="problem-card rounded-xl border border-red-200 bg-red-50 p-3">
                                        <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="card.title"></p>
                                        <p class="text-xs text-red-700 mt-1.5 font-semibold">Catatan dosen:</p>
                                        <p class="text-xs text-red-600 mt-0.5 line-clamp-3" x-text="card.note || card.feedback"></p>
                                        <template x-if="isPm">
                                            <div class="mt-2.5 space-y-1.5 border-t border-red-100 pt-2.5">
                                                <input x-model="card._editTitle" type="text" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400" :placeholder="card.title">
                                                <textarea x-model="card._editDesc" rows="2" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-400 resize-none" :placeholder="card.description"></textarea>
                                                <button type="button" @click="resubmitCard(card)" class="w-full rounded-lg bg-blue-600 px-2 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition">
                                                    Ajukan ulang ke dosen
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Selesai --}}
                        <div id="board-done" class="w-72 shrink-0 rounded-2xl border border-emerald-200 bg-white shadow-sm flex flex-col">
                            <div class="rounded-t-2xl bg-emerald-50 border-b border-emerald-100 px-3 py-2.5 shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <p class="text-xs font-bold text-emerald-700">Selesai</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-600" x-text="board.selesai.length"></span>
                                </div>
                                <p class="text-[10px] text-emerald-600 mt-1">Disetujui dosen</p>
                            </div>
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                <p x-show="board.selesai.length === 0" class="text-xs text-slate-400 italic py-8 text-center">Belum ada yang selesai.</p>
                                <template x-for="card in board.selesai" :key="'done-' + card.id">
                                    <div :data-id="card.id" class="problem-card rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                                        <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="card.title"></p>
                                        <div class="flex items-center gap-1.5 mt-2">
                                            <i class="fas fa-check-circle text-[10px] text-emerald-500"></i>
                                            <p class="text-xs text-emerald-700 font-medium">Disetujui <span x-text="card.date ? '· ' + card.date : ''"></span></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <aside class="flex flex-col gap-6">
            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Project Team</h3>
                <div class="space-y-4">
                    @forelse($teamMembers ?? [] as $member)
                    <div class="flex items-center gap-3 rounded-3xl bg-slate-50 p-4">
                        <div class="h-11 w-11 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">{{ $member['initials'] }}</div>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $member['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $member['role'] }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-slate-400 text-center py-4">Belum ada anggota tim.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm flex flex-col flex-1">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Diskusi</h3>
                <div x-ref="commentList" class="space-y-3 text-sm text-slate-700 flex-1 min-h-0 overflow-y-auto mb-4">
                    <p x-show="comments.filter(c => c.problem_id === null).length === 0" class="text-sm text-slate-400 text-center py-4">Belum ada pesan. Mulai diskusi tim di sini.</p>
                    <template x-for="comment in comments.filter(c => c.problem_id === null)" :key="'c-' + comment.id">
                        <div class="rounded-2xl bg-slate-50 p-3 border border-slate-200">
                            <div class="flex items-start justify-between gap-2 text-[11px] text-slate-500">
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-700" x-text="comment.from"></span>
                                    <template x-if="comment.problem_title">
                                        <span class="ml-1 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 truncate max-w-[140px]" x-text="comment.problem_title"></span>
                                    </template>
                                </div>
                                <span class="shrink-0" x-text="comment.time"></span>
                            </div>
                            <p class="mt-1 text-sm text-slate-700 whitespace-pre-line" x-text="comment.text"></p>
                            <button type="button" @click="toggleReply(comment.id)"
                                    class="mt-2 text-[11px] font-semibold text-blue-600 hover:text-blue-800 transition">
                                Balas
                            </button>
                            <div x-show="replyingTo === comment.id" x-cloak class="mt-2 space-y-2">
                                <textarea x-model="replyDraft" rows="2"
                                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-blue-400 resize-none"
                                          placeholder="Tulis balasan..."></textarea>
                                <div class="flex gap-2">
                                    <button type="button" @click="postReply(comment)" :disabled="loading"
                                            class="flex-1 rounded-lg bg-blue-600 px-2 py-1.5 text-[11px] font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                                        Kirim balasan
                                    </button>
                                    <button type="button" @click="cancelReply()"
                                            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                                        Batal
                                    </button>
                                </div>
                            </div>
                            <template x-if="comment.replies && comment.replies.length">
                                <div class="mt-3 ml-3 space-y-2 border-l-2 border-slate-200 pl-3">
                                    <template x-for="reply in comment.replies" :key="'r-' + reply.id">
                                        <div class="rounded-xl bg-white p-2.5 border border-slate-100">
                                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                                <span class="font-semibold text-slate-700" x-text="reply.from"></span>
                                                <span x-text="reply.time"></span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600 whitespace-pre-line" x-text="reply.text"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <!-- Chat input -->
                <div class="border-t border-slate-100 pt-4">
                    <div class="flex gap-2 items-end">
                        <textarea x-model="generalDraft"
                                  @keydown.enter.prevent="if (!$event.shiftKey) postGeneralMessage()"
                                  rows="2"
                                  class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:bg-white resize-none transition"
                                  placeholder="Tulis pesan diskusi... (Enter untuk kirim)"></textarea>
                        <button type="button"
                                @click="postGeneralMessage()"
                                :disabled="loading || !generalDraft.trim()"
                                class="shrink-0 h-10 w-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition disabled:opacity-40">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

        </aside>
    </div>
</div>

@push('scripts')
<script>
function problemBoardApp(config) {
    return {
        ...config,
        loading: false,
        flash: '',
        flashType: 'success',
        cardDrafts: {},
        replyingTo: null,
        replyDraft: '',
        generalDraft: '',
        form: { title: '', description: '', category: 'Teknik', priority: 'Sedang', attachments: [{ type: 'link', value: '', preview: null }] },
        get votingOpen() { return this.board.voting.length > 0; },
        init() {
            this.board.perbaiki.forEach(c => {
                c._editTitle = c.title;
                c._editDesc = c.description;
            });
            this.$nextTick(() => this.initSortable());
        },
        sortedVotingCards() {
            return [...this.board.voting].sort((a, b) => Number(b.votes || 0) - Number(a.votes || 0));
        },
        showFlash(msg, type = 'success') {
            this.flash = msg;
            this.flashType = type;
            setTimeout(() => { this.flash = ''; }, 5000);
        },
        async apiPost(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Permintaan gagal.';
                throw new Error(msg);
            }
            return data;
        },
        applyBoard(board) {
            if (!board) return;
            this.board = board;
            this.votersCount = board.voting.reduce((n, c) => n + (c.is_my_vote ? 1 : 0), 0);
            const distinct = new Set();
            board.voting.forEach(c => { if (c.votes > 0) distinct.add(c.id); });
        },
        async addIdeaCard() {
            if (!this.form.title.trim()) return;
            this.loading = true;
            try {
                const filled = this.form.attachments.filter(a => a.value.trim());
                const payload = {
                    title: this.form.title,
                    description: this.form.description,
                    category: this.form.category,
                    priority: this.form.priority,
                    attachment: filled.length > 0
                        ? JSON.stringify(filled.map(a => ({ type: a.type, value: a.value })))
                        : null,
                };
                const data = await this.apiPost(this.routes.store, payload);
                this.board.ide.unshift(data.card);
                this.form = { title: '', description: '', category: 'Teknik', priority: 'Sedang', attachments: [{ type: 'link', value: '', preview: null }] };
                this.showForm = false;
                this.showFlash('Ide masalah ditambahkan.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async proposeVoting(card) {
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.propose, { problem_id: card.id });
                this.board = data.board;
                this.showFlash('Ide dipindahkan ke voting.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        startEditIdea(card) {
            card._editTitle = card.title;
            card._editDesc = card.description || '';
            card._editCategory = card.category;
            card._editPriority = card.priority;
            card._editing = true;
        },
        async saveIdea(card) {
            if (!(card._editTitle || '').trim()) {
                this.showFlash('Judul masalah tidak boleh kosong.', 'error');
                return;
            }
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.update, {
                    problem_id: card.id,
                    title: card._editTitle,
                    description: card._editDesc,
                    category: card._editCategory,
                    priority: card._editPriority,
                });
                this.board = data.board;
                this.showFlash('Ide masalah diperbarui.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async deleteIdea(card) {
            if (!confirm('Anda yakin ingin menghapus ide masalah "' + card.title + '"? Tindakan ini tidak dapat dibatalkan.')) return;
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.delete, { problem_id: card.id });
                this.board = data.board;
                this.showFlash('Ide masalah dihapus.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async castVote(card) {
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.vote, { problem_id: card.id });
                this.board = data.board;
                if (data.voters_count !== undefined) {
                    this.votersCount = data.voters_count;
                }
                if (data.auto_submitted) {
                    this.showFlash('Semua anggota sudah vote. Masalah utama otomatis diajukan ke dosen.');
                }
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        toggleReply(commentId) {
            if (this.replyingTo === commentId) {
                this.cancelReply();
                return;
            }
            this.replyingTo = commentId;
            this.replyDraft = '';
        },
        cancelReply() {
            this.replyingTo = null;
            this.replyDraft = '';
        },
        async postComment(card) {
            const message = (this.cardDrafts[card.id] || '').trim();
            if (!message) {
                this.showFlash('Tulis komentar terlebih dahulu.', 'error');
                return;
            }
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.comment, {
                    problem_id: card.id,
                    message,
                });
                this.comments = data.comments || [];
                this.cardDrafts[card.id] = '';
                this.showFlash('Komentar terkirim.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async postReply(comment) {
            const message = (this.replyDraft || '').trim();
            if (!message) {
                this.showFlash('Tulis balasan terlebih dahulu.', 'error');
                return;
            }
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.discuss, {
                    message,
                    parent_id: comment.id,
                });
                this.comments = data.comments || [];
                this.cancelReply();
                this.showFlash('Balasan terkirim.');
                this.$nextTick(() => this.scrollComments());
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async postGeneralMessage() {
            const message = (this.generalDraft || '').trim();
            if (!message) return;
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.discuss, { message });
                this.comments = data.comments || [];
                this.generalDraft = '';
                this.$nextTick(() => this.scrollComments());
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        scrollComments() {
            const el = this.$refs.commentList;
            if (el) el.scrollTop = el.scrollHeight;
        },
        countVotersFromBoard(board) {
            return board.voting.length;
        },
        async submitToLecturer() {
            if (!this.isPm) return;
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.submit, {});
                this.board = data.board;
                this.showFlash('Masalah utama diajukan ke dosen.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        addAttachment() {
            this.form.attachments.push({ type: 'link', value: '', preview: null });
        },
        removeAttachment(index) {
            this.form.attachments.splice(index, 1);
        },
        handleAttachmentImage(event, index) {
            const file = event.target.files[0];
            if (!file) return;
            this.form.attachments[index].value = file.name;
            const reader = new FileReader();
            reader.onload = (e) => { this.form.attachments[index].preview = e.target.result; };
            reader.readAsDataURL(file);
        },
        async resubmitCard(card) {
            this.loading = true;
            try {
                const data = await this.apiPost(this.routes.resubmit, {
                    problem_id: card.id,
                    title: card._editTitle || card.title,
                    description: card._editDesc || card.description,
                    category: card.category,
                    priority: card.priority,
                    attachment: card.attachment_link || '',
                });
                this.board = data.board;
                this.showFlash('Masalah diajukan ulang ke dosen.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        initSortable(force = false) {
            const columnIds = ['board-idea', 'board-voting', 'board-submitted', 'board-revision', 'board-done'];
            columnIds.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (force && el._sortable) {
                    el._sortable.destroy();
                    el._sortable = null;
                    el._sortableInit = false;
                }
                if (el._sortableInit) return;
                el._sortableInit = true;
                el._sortable = new Sortable(el, {
                    group: { name: id, pull: false, put: false },
                    animation: 150,
                    draggable: '.problem-card',
                    onMove(evt) {
                        if (evt.from !== evt.to) return false;
                    },
                });
            });
        },
    };
}
</script>
@endpush
