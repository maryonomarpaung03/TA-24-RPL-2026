@php
    $projectId = $selected_project['id'];
@endphp
<div class="space-y-6" x-data="problemBoardApp({
    projectId: @js($projectId),
    editMode: false,
    isPm: @js($isPm ?? false),
    participantCount: @js($participantCount ?? 1),
    votersCount: @js($votersCount ?? 0),
    board: @js($problemBoard ?? ['ide' => [], 'voting' => [], 'diajukan' => [], 'perbaiki' => [], 'selesai' => []]),
    comments: @js($problemComments ?? []),
    routes: {
        store: @js(route('problem.store', $projectId)),
        propose: @js(route('problem.propose-voting', $projectId)),
        vote: @js(route('problem.vote', $projectId)),
        comment: @js(route('problem.comment', $projectId)),
        submit: @js(route('problem.submit-lecturer', $projectId)),
        resubmit: @js(route('problem.resubmit', $projectId)),
    },
    csrf: @js(csrf_token()),
})">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Problem Identification</p>
                <h2 class="text-3xl font-bold text-slate-900">{{ $selected_project['name'] }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $selected_project['description'] }}</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="inline-flex rounded-full bg-slate-100 p-1 border border-slate-200">
                    <button type="button" @click="switchToView()" :class="!editMode ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-full px-4 py-2 text-xs font-bold transition">Lihat</button>
                    <button type="button" @click="switchToEdit()" :class="editMode ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-full px-4 py-2 text-xs font-bold transition">Edit</button>
                </div>
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

    <div class="grid grid-cols-1 xl:grid-cols-[1.9fr_1fr] gap-6">
        <div class="min-w-0">
        {{-- Mode Edit: card form input --}}
        <div x-show="editMode" x-cloak>
            <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm">
                <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-1">Form Input</h3>
                <p class="text-sm text-slate-500 mb-6">Ajukan ide masalah baru ke board tim.</p>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-4">Identifikasi Masalah</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-500">Judul Masalah (Card)</label>
                            <input x-model="form.title" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="Contoh: Input data absensi masih manual">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-500">Deskripsi</label>
                            <textarea x-model="form.description" rows="3" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="Detail masalah / konteks singkat..."></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Kategori</label>
                            <select x-model="form.category" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400">
                                <option>Teknik</option>
                                <option>Diskusi</option>
                                <option>Etika</option>
                                <option>Kebutuhan Proyek</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Prioritas</label>
                            <select x-model="form.priority" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400">
                                <option>Tinggi</option>
                                <option>Sedang</option>
                                <option>Rendah</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-slate-500">Attachments (nama file/link)</label>
                            <input x-model="form.attachment" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="mis. bukti-error.pdf">
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button type="button" @click="addIdeaCard()" :disabled="loading" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition disabled:opacity-50">
                            <i class="fas fa-plus"></i> Tambah ke Board
                        </button>
                        <button type="button" @click="switchToView()" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                            Lihat alur board
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mode Lihat: card flow board --}}
        <div x-show="!editMode">
            <div class="bg-white rounded-[2rem] border border-slate-200 p-6 lg:p-8 shadow-sm">
                <div class="flex items-center justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold">Alur Identifikasi Masalah</h3>
                        <p class="text-xs text-slate-500 mt-1">Pantau ide, voting, pengajuan, dan persetujuan dosen.</p>
                    </div>
                    <button type="button" @click="switchToEdit()" class="shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                        <i class="fas fa-plus"></i> Tambah ide
                    </button>
                </div>
                <div class="rounded-[1.75rem] bg-slate-100 border border-slate-200 p-4 min-h-[520px]">
                    <div class="flex gap-3 justify-between text-sm items-stretch">
                        <div id="board-idea" class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[420px] flex flex-col">
                            <div class="border-b border-slate-200 pb-3 mb-4 shrink-0">
                                <p class="font-bold text-slate-700">Ide Masalah</p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <p x-show="board.ide.length === 0" class="text-[11px] text-slate-400 italic py-4 text-center">Belum ada ide masalah.</p>
                                <template x-for="card in board.ide" :key="'ide-' + card.id">
                                    <div :data-id="card.id" class="problem-card mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                        <p class="font-semibold text-slate-800" x-text="card.title"></p>
                                        <p class="text-[10px] text-slate-400 mt-1" x-text="card.author_name"></p>
                                        <p class="text-[11px] text-slate-500 mt-1"><span x-text="card.category"></span> &bull; <span x-text="card.priority"></span></p>
                                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2" x-text="card.description"></p>
                                        <button type="button" @click="proposeVoting(card)" class="mt-2 w-full rounded-lg border border-blue-200 bg-blue-50 px-2 py-2 text-[11px] font-semibold text-blue-700 hover:bg-blue-100 transition">
                                            Ajukan untuk voting
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div id="board-voting" class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[420px] flex flex-col">
                            <div class="border-b border-slate-200 pb-3 mb-4 shrink-0">
                                <p class="font-bold text-slate-700">Voting</p>
                                <span class="mt-1 inline-block rounded-full px-2 py-1 text-[10px] font-semibold"
                                      :class="votingOpen ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'"
                                      x-text="votingOpen ? 'Dibuka' : 'Ditutup'"></span>
                                <p class="text-[10px] text-slate-400 mt-2" x-text="votersCount + ' / ' + participantCount + ' anggota sudah vote'"></p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <template x-for="card in sortedVotingCards()" :key="'vote-' + card.id">
                                    <div :data-id="card.id" class="problem-card mb-2 rounded-xl border p-2.5"
                                         :class="card.is_my_vote ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-slate-50'">
                                        <p class="font-semibold text-slate-800" x-text="card.title"></p>
                                        <p class="text-[11px] text-slate-500 mt-1">Vote: <span class="font-semibold" x-text="card.votes ?? 0"></span></p>
                                        <button type="button" @click="castVote(card)" :disabled="!votingOpen || loading"
                                                class="mt-2 w-full rounded-lg px-2 py-2 text-xs font-semibold text-white transition disabled:opacity-40"
                                                :class="card.is_my_vote ? 'bg-slate-600 hover:bg-slate-700' : 'bg-blue-600 hover:bg-blue-700'"
                                                x-text="card.is_my_vote ? 'Batalkan vote' : 'Vote'"></button>
                                        <div class="mt-2 border-t border-slate-200 pt-2">
                                            <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Komentar</label>
                                            <textarea x-model="cardDrafts[card.id]" rows="2"
                                                      class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] outline-none focus:border-blue-400 resize-none"
                                                      placeholder="Tulis komentar untuk ide ini..."></textarea>
                                            <button type="button" @click="postComment(card)" :disabled="loading || !votingOpen"
                                                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 transition disabled:opacity-40">
                                                <i class="fas fa-comment-dots mr-1"></i> Kirim komentar
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="isPm && board.voting.length > 0" class="mt-3 pt-3 border-t border-slate-100 shrink-0">
                                <button type="button" @click="submitToLecturer()" :disabled="loading"
                                        class="w-full rounded-lg bg-slate-900 px-2 py-2 text-[11px] font-semibold text-white hover:bg-slate-800 transition disabled:opacity-50">
                                    Ajukan ke Dosen
                                </button>
                                <p class="text-[10px] text-slate-400 mt-1 text-center">Otomatis saat semua anggota sudah vote</p>
                            </div>
                        </div>

                        <div id="board-submitted" class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[420px] flex flex-col">
                            <div class="border-b border-slate-200 pb-3 mb-4 shrink-0">
                                <p class="font-bold text-slate-700">Diajukan</p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <template x-for="card in board.diajukan" :key="'aju-' + card.id">
                                    <div :data-id="card.id" class="problem-card mb-2 rounded-xl border border-amber-200 bg-amber-50 p-2.5">
                                        <p class="font-semibold text-slate-800" x-text="card.title"></p>
                                        <p class="text-[11px] text-amber-700 mt-1" x-text="card.status_label || 'Menunggu Persetujuan Dosen'"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div id="board-revision" class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[420px] flex flex-col">
                            <div class="border-b border-slate-200 pb-3 mb-4 shrink-0">
                                <p class="font-bold text-slate-700">Perbaiki</p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <template x-for="card in board.perbaiki" :key="'fix-' + card.id">
                                    <div :data-id="card.id" class="problem-card mb-2 rounded-xl border border-red-200 bg-red-50 p-2.5">
                                        <p class="font-semibold text-slate-800" x-text="card.title"></p>
                                        <p class="text-[11px] text-red-700 mt-1 font-semibold">Catatan dosen:</p>
                                        <p class="text-[11px] text-red-600" x-text="card.note || card.feedback"></p>
                                        <template x-if="isPm">
                                            <div class="mt-2 space-y-2">
                                                <input x-model="card._editTitle" type="text" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-[11px]" :placeholder="card.title">
                                                <textarea x-model="card._editDesc" rows="2" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-[11px]" :placeholder="card.description"></textarea>
                                                <button type="button" @click="resubmitCard(card)" class="w-full rounded-lg bg-blue-600 px-2 py-2 text-[11px] font-semibold text-white hover:bg-blue-700">Ajukan ulang ke dosen</button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div id="board-done" class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[420px] flex flex-col">
                            <div class="border-b border-slate-200 pb-3 mb-4 shrink-0">
                                <p class="font-bold text-slate-700">Selesai</p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                <template x-for="card in board.selesai" :key="'done-' + card.id">
                                    <div :data-id="card.id" class="problem-card mb-2 rounded-xl border border-emerald-200 bg-emerald-50 p-2.5">
                                        <p class="font-semibold text-slate-800" x-text="card.title"></p>
                                        <p class="text-[11px] text-emerald-700 mt-1">Disetujui dosen <span x-text="card.date ? '· ' + card.date : ''"></span></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <aside class="space-y-6">
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

            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Diskusi</h3>
                <div class="space-y-3 text-sm text-slate-700 max-h-[420px] overflow-y-auto">
                    <p x-show="comments.length === 0" class="text-sm text-slate-400 text-center py-4">Belum ada komentar.</p>
                    <template x-for="comment in comments" :key="'c-' + comment.id">
                        <div class="rounded-2xl bg-slate-50 p-3 border border-slate-200">
                            <div class="flex items-start justify-between gap-2 text-[11px] text-slate-500">
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-700" x-text="comment.from"></span>
                                    <span class="ml-1 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 truncate max-w-[140px]" x-text="comment.problem_title"></span>
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
            </div>

            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Flow Persetujuan</h3>
                <ol class="space-y-2 text-sm text-slate-700 list-decimal pl-4">
                    <li>Setiap mahasiswa mengajukan ide masalah (mode Edit).</li>
                    <li>Ajukan ide ke voting; semua anggota vote (1 suara per orang).</li>
                    <li>Project Manager mengajukan ke dosen, atau otomatis setelah semua vote.</li>
                    <li>Dosen review di notifikasi; setujui atau minta perbaiki.</li>
                    <li>Project Manager ajukan ulang dari kolom Perbaiki hingga disetujui.</li>
                </ol>
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
        form: { title: '', description: '', category: 'Teknik', priority: 'Sedang', attachment: '' },
        get votingOpen() { return this.board.voting.length > 0; },
        init() {
            this.board.perbaiki.forEach(c => {
                c._editTitle = c.title;
                c._editDesc = c.description;
            });
            if (!this.editMode) {
                this.$nextTick(() => this.initSortable());
            }
        },
        switchToView() {
            this.editMode = false;
            this.$nextTick(() => this.initSortable(true));
        },
        switchToEdit() {
            this.editMode = true;
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
                const data = await this.apiPost(this.routes.store, this.form);
                this.board.ide.unshift(data.card);
                this.form = { title: '', description: '', category: 'Teknik', priority: 'Sedang', attachment: '' };
                this.showFlash('Ide masalah ditambahkan.');
                this.switchToView();
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
                const data = await this.apiPost(this.routes.comment, {
                    problem_id: comment.problem_id,
                    message,
                    parent_id: comment.id,
                });
                this.comments = data.comments || [];
                this.cancelReply();
                this.showFlash('Balasan terkirim.');
            } catch (e) {
                this.showFlash(e.message, 'error');
            } finally {
                this.loading = false;
            }
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
