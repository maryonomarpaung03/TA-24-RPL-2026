@extends('layouts.app')

@section('title', 'Dashboard - DELPRO')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush

@section('content')
<div class="flex-1 p-6 overflow-y-auto">

            @if(!empty($selected_project) && !($selected_project['can_access_pjbl'] ?? false))
            @include('partials.project-pjbl-gate')
            @elseif(!empty($selected_project))
            <div class="space-y-6" x-data="{
                editMode: @js($initialEditMode ?? false),
                form: {
                    title: '',
                    description: '',
                    category: 'Teknis',
                    priority: 'Sedang',
                    attachment: ''
                },
                board: @js($problemBoard ?? ['ide' => [], 'voting' => [], 'diajukan' => [], 'perbaiki' => [], 'selesai' => []]),
                comments: @js($problemComments ?? []),
                votingOpen: false,
                participantCount: @js($participantCount ?? 1),
                myVoteKey: null,
                totalVotes() {
                    return this.board.voting.reduce((sum, c) => sum + Number(c.votes || 0), 0);
                },
                votedCount() {
                    return this.totalVotes();
                },
                unvotedCount() {
                    const remaining = this.participantCount - this.votedCount();
                    return remaining > 0 ? remaining : 0;
                },
                sortedVotingCards() {
                    return [...this.board.voting].sort((a, b) => Number(b.votes || 0) - Number(a.votes || 0));
                },
                isMyVote(card) {
                    return this.myVoteKey === card.key;
                },
                toggleVote(card) {
                    if (!this.votingOpen) return;
                    const current = this.board.voting.find((item) => item.key === card.key);
                    if (!current) return;
                    if (this.myVoteKey === card.key) {
                        current.votes = Math.max(0, Number(current.votes || 0) - 1);
                        this.myVoteKey = null;
                        return;
                    }
                    if (this.myVoteKey) {
                        const previous = this.board.voting.find((item) => item.key === this.myVoteKey);
                        if (previous) previous.votes = Math.max(0, Number(previous.votes || 0) - 1);
                    }
                    current.votes = Number(current.votes || 0) + 1;
                    this.myVoteKey = card.key;
                },
                topVotingCard() {
                    const sorted = this.sortedVotingCards();
                    return sorted.length ? sorted[0] : null;
                },
                submitTopToLecturer() {
                    const top = this.topVotingCard();
                    if (!top) return;
                    this.board.diajukan.unshift({
                        title: top.title,
                        status: 'Menunggu Persetujuan Dosen',
                        category: top.category,
                        priority: top.priority
                    });
                    this.votingOpen = false;
                },
                addIdeaCard() {

    if (
        !this.form.title.trim()
    ) return;

    fetch(
        '{{ route("problem.store", $selected_project["id"]) }}',
        {
            method: 'POST',

            headers: {
                'Content-Type':
                    'application/json',

                'Accept':
                    'application/json',

                'X-CSRF-TOKEN':
                    '{{ csrf_token() }}'
            },

            body: JSON.stringify({

                title:
                    this.form.title,

                description:
                    this.form.description,

                category:
                    this.form.category,

                priority:
                    this.form.priority,

                attachment:
                    this.form.attachment
            })
        }
    )
    .then(response =>
        response.json()
    )
    .then(data => {

        if (
            data.success
        ) {

            this.board.ide.unshift(
                data.card
            );

            this.form.title = '';
            this.form.description = '';
            this.form.attachment = '';
        }
    })
    .catch(error => {

        console.error(
            'Gagal simpan problem:',
            error
        );
    });
}
                    
            }">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Problem Identification</p>
                            <h2 class="text-3xl font-bold text-slate-900">{{ $selected_project['name'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $selected_project['description'] }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="inline-flex rounded-full bg-slate-100 p-1 border border-slate-200">
                                <button type="button" @click="editMode = false" :class="editMode ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-blue-700 shadow-sm'" class="rounded-full px-4 py-2 text-xs font-bold transition">Lihat</button>
                                <button type="button" @click="editMode = true" :class="editMode ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-full px-4 py-2 text-xs font-bold transition">Edit</button>
                            </div>
                            <button type="button" x-show="editMode" x-cloak class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                                <i class="fas fa-save"></i>
                                Simpan
                            </button>
                            @if(($selected_project['is_draft'] ?? false) && (int) auth()->id() === (int) ($selected_project['created_by'] ?? 0))
                            <form method="POST" action="{{ route('projek.submit', $selected_project['id']) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
                                    <i class="fas fa-paper-plane"></i>
                                    Ajukan ke Dosen
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if($selected_project['is_pending'] ?? false)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Proyek sudah diajukan ke dosen (<strong>{{ $selected_project['lecturer_email'] }}</strong>) dan menunggu persetujuan.
                </div>
                @endif

                @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('info'))
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
                @endif
                @if(session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
                @endif

                <div class="grid grid-cols-1 xl:grid-cols-[1.9fr_1fr] gap-6">
                    <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-4">Form Input Tugas/Masalah</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-semibold text-slate-500">Judul Masalah (Card)</label>
                                        <input x-model="form.title" x-bind:disabled="!editMode" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="Contoh: Input data absensi masih manual">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-semibold text-slate-500">Deskripsi</label>
                                        <textarea x-model="form.description" x-bind:disabled="!editMode" rows="3" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="Detail masalah / konteks singkat..."></textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500">Custom Field (Kategori)</label>
                                        <select x-model="form.category" x-bind:disabled="!editMode" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400">
                                            <option>Teknis</option>
                                            <option>Diskusi</option>
                                            <option>Etika</option>
                                            <option>Kebutuhan Proyek</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500">Prioritas</label>
                                        <select x-model="form.priority" x-bind:disabled="!editMode" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400">
                                            <option>Tinggi</option>
                                            <option>Sedang</option>
                                            <option>Rendah</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-semibold text-slate-500">Attachments (nama file/link)</label>
                                        <input x-model="form.attachment" x-bind:disabled="!editMode" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-blue-400" placeholder="mis. bukti-error.pdf">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button x-show="editMode" x-cloak type="button" @click="addIdeaCard()" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition">
                                        <i class="fas fa-plus"></i> Tambah ke Board
                                    </button>
                                </div>
                            </div>

<div class="rounded-[1.75rem] bg-slate-100 border border-slate-200 p-4">
    <div class="flex gap-3 justify-between text-sm">

        <!-- IDE -->
        <div
            id="board-idea"
            class="flex-1 min-w-0 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm min-h-[360px] flex flex-col">

            <div class="border-b border-slate-200 pb-3 mb-4">
                <p class="font-bold text-slate-700">
                    Ide Masalah
                </p>
            </div>

            <p
                x-show="board.ide.length === 0"
                class="text-[11px] text-slate-400 italic py-4 text-center">
                Belum ada ide masalah.
            </p>

            <template
                x-for="(card, idx) in board.ide"
                :key="'ide-' + idx">

                <div
                    :data-id="card.id"
                    class="problem-card mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 cursor-move">

                    <p
                        class="font-semibold text-slate-800"
                        x-text="card.title">
                    </p>

                    <p class="text-[11px] text-slate-500 mt-1">
                        <span x-text="card.category"></span>
                        •
                        <span x-text="card.priority"></span>
                    </p>

                    <p class="text-[11px] text-slate-500 mt-1">
                        <span x-text="card.description"></span>
                    </p>

                </div>
            </template>
        </div>

        <!-- VOTING -->
        <div
            id="board-voting"
            class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white p-2.5 shrink-0">

            <div class="border-b border-slate-200 pb-3 mb-4">
                <p class="font-bold text-slate-700">
                    Voting
                </p>

                <span
                    class="rounded-full px-2 py-1 text-[10px] font-semibold"
                    :class="votingOpen
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-200 text-slate-700'"
                    x-text="votingOpen
                        ? 'Dibuka'
                        : 'Ditutup'">
                </span>
            </div>

            <template
                x-for="(card, idx) in board.voting"
                :key="'vote-' + idx">

                <div
                    :data-id="card.id"
                    class="problem-card mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 cursor-move">

                    <p
                        class="font-semibold text-slate-800"
                        x-text="card.title">
                    </p>

                    <p class="text-[11px] text-slate-500 mt-1">
                        Vote:
                        <span
                            class="font-semibold"
                            x-text="card.votes ?? 0">
                        </span>
                    </p>

                    <button
                        type="button"
                        @click="toggleVote(card)"
                        class="mt-2 w-full rounded-lg bg-blue-600 px-2 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition">

                        Vote
                    </button>
                </div>
            </template>
        </div>

        <!-- DIAJUKAN -->
        <div
            id="board-submitted"
            class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white p-2.5 shrink-0">

           <div class="border-b border-slate-200 pb-3 mb-4">
                <p class="font-bold text-slate-700">
                    Diajukan
                </p>
            </div>

            <template
                x-for="(card, idx) in board.diajukan"
                :key="'aju-' + idx">

                <div
                    :data-id="card.id"
                    class="problem-card mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 cursor-move">

                    <p
                        class="font-semibold text-slate-800"
                        x-text="card.title">
                    </p>

                    <p
                        class="text-[11px] text-amber-600 mt-1"
                        x-text="card.status">
                    </p>

                </div>
            </template>
        </div>

        <!-- PERBAIKI -->
        <div
            id="board-revision"
            class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white p-2.5 shrink-0">

            <div class="border-b border-slate-200 pb-3 mb-4">
                <p class="font-bold text-slate-700">
                    Perbaiki
                </p>
            </div>

            <template
                x-for="(card, idx) in board.perbaiki"
                :key="'fix-' + idx">

                <div
                    :data-id="card.id"
                    class="problem-card mb-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 cursor-move">

                    <p
                        class="font-semibold text-slate-800"
                        x-text="card.title">
                    </p>

                    <p
                        class="text-[11px] text-red-600 mt-1"
                        x-text="card.note">
                    </p>

                </div>
            </template>
        </div>

        <!-- DONE -->
        <div
            id="board-done"
            class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white p-2.5 shrink-0">

            <div class="border-b border-slate-200 pb-3 mb-4">
                <p class="font-bold text-slate-700">
                    Selesai
                </p>
            </div>
            <template
                x-for="(card, idx) in board.selesai"
                :key="'done-' + idx">

                <div
                    :data-id="card.id"
                    class="problem-card mb-2 rounded-xl border border-emerald-200 bg-emerald-50 p-2.5 cursor-move">

                    <p
                        class="font-semibold text-slate-800"
                        x-text="card.title">
                    </p>

                    <p class="text-[11px] text-emerald-700 mt-1">
                        Final:
                        <span x-text="card.date"></span>
                    </p>

                </div>
            </template>
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
                            <button type="button" x-show="editMode" class="mt-6 w-full rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Manage Team</button>
                        </div>

                        <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Assigned Comments</h3>
                            <div class="space-y-3 text-sm text-slate-700">
                                <p x-show="comments.length === 0" class="text-sm text-slate-400 text-center py-4">Belum ada komentar.</p>
                                <template x-for="(comment, idx) in comments" :key="'c-' + idx">
                                    <div class="rounded-2xl bg-slate-50 p-3 border border-slate-200">
                                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                                            <span class="font-semibold" x-text="comment.from"></span>
                                            <span x-text="comment.time"></span>
                                        </div>
                                        <p class="mt-1 text-[11px] text-blue-700">Card: <span x-text="comment.target"></span></p>
                                        <p class="mt-1 text-sm text-slate-700" x-text="comment.text"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Flow Persetujuan (Sesuai Alur)</h3>
                            <ol class="space-y-2 text-sm text-slate-700 list-decimal pl-4">
                                <li>Setiap mahasiswa mengajukan ide masalah.</li>
                                <li>Mahasiswa melakukan voting masalah paling cocok.</li>
                                <li>Masalah hasil voting diajukan ke dosen.</li>
                                <li>Dosen menyetujui? Jika tidak, masuk kolom <span class="font-semibold text-red-600">Perbaiki</span>.</li>
                                <li>Jika ya, masalah dipindah ke kolom <span class="font-semibold text-emerald-600">Selesai</span>.</li>
                            </ol>
                        </div>
                    </aside>
                </div>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @foreach ($statistics as $key => $value)
                <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500 text-center">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase mb-2">{{ str_replace('_', ' ', $key) }}</h3>
                    <p class="text-3xl font-extrabold">{{ $value }}</p>
                </div>
                @endforeach
            </div>

            <!-- CHARTS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-4 rounded shadow"><h3 class="text-xs font-bold text-gray-400 uppercase mb-4 text-center">Status Projek Terdistribusi</h3><div class="chart-container"><canvas id="pieChart"></canvas></div></div>
                <div class="bg-white p-4 rounded shadow"><h3 class="text-xs font-bold text-gray-400 uppercase mb-4 text-center">Ulasan Progres Projek</h3><div class="chart-container"><canvas id="barChart"></canvas></div></div>
            </div>

            <!-- BOTTOM -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded shadow">
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Projek Berlangsung</h3><a href="{{ route('my-project') }}" class="text-blue-500 text-xs font-bold hover:underline">Lihat semua →</a></div>
                    @foreach ($ongoing_projects as $p)<div class="mb-5"><h4 class="text-sm font-bold">{{ $p['name'] }}</h4><div class="flex justify-between text-[10px] mt-1"><span class="text-gray-400">Deadline: {{ $p['deadline'] }}</span><span class="text-blue-600 font-bold">{{ $p['progress'] }}%</span></div><div class="bg-gray-100 h-2 rounded-full mt-1"><div class="bg-blue-600 h-2 rounded-full" style="width: {{ $p['progress'] }}%"></div></div></div>@endforeach
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Deadline Tugas (7 Hari)</h3><a href="#" class="text-blue-500 text-xs font-bold">Lihat semua →</a></div>
                    @foreach ($deadlines as $d)<div class="flex justify-between items-center mb-4 p-2 hover:bg-gray-50 rounded"><div class="flex items-center space-x-3"><div class="w-2 h-2 rounded-full {{ $d['priority'] == 'red' ? 'bg-red-500' : 'bg-yellow-400' }}"></div><div><h4 class="text-sm font-bold">{{ $d['task'] }}</h4><p class="text-[10px] text-gray-400">{{ $d['project'] }}</p></div></div><span class="text-sm font-black">{{ $d['days_left'] }}d</span></div>@endforeach
                </div>
            </div>
            @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener(
    'DOMContentLoaded',
    () => {

        const boards = {

            'board-idea':
                'idea',

            'board-voting':
                'voting',

            'board-submitted':
                'submitted',

            'board-revision':
                'revision',

            'board-done':
                'done',
        };

        Object.keys(
            boards
        ).forEach(
            boardId => {

                const el =
                    document.getElementById(
                        boardId
                    );

                if (!el) return;
                

                console.log('Attach sortable:', boardId);
                new Sortable(
                    el,
                    {
                        group:
                            'problem-board',

                        animation:
                            150,

                        draggable:
                            '.problem-card',

                        onEnd:
                            function (evt) {
                                console.log('DRAG WORKING');
                                console.log(evt);

                                const card =
                                    evt.item;

                                const problemId =
                                    card.dataset.id;

                                const newStatus =
                                    boards[
                                        evt.to.id
                                    ];

                                fetch(
                                    '{{ route("problem.move") }}',
                                    {
                                        method:
                                            'POST',

                                        headers: {
                                            'Content-Type':
                                                'application/json',

                                            'Accept':
                                                'application/json',

                                            'X-CSRF-TOKEN':
                                                '{{ csrf_token() }}'
                                        },

                                        body:
                                            JSON.stringify({

                                                problem_id:
                                                    problemId,

                                                status:
                                                    newStatus
                                            })
                                    }
                                )
                                .then(
                                    response =>
                                        response.json()
                                )
                                .then(
                                    data => {

                                        console.log(
                                            'Moved',
                                            data
                                        );
                                    }
                                )
                                .catch(
                                    err => {

                                        console.error(
                                            err
                                        );
                                    }
                                );
                            }
                    }
                );
            }
        );
    }
);


(function () {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } };
    const pieEl = document.getElementById('pieChart');
    const barEl = document.getElementById('barChart');
    if (pieEl) {
        new Chart(pieEl, { type: 'pie', data: { labels: ['Ongoing', 'Planning', 'Completed'], datasets: [{ data: @json(array_values($pie_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderWidth: 0 }] }, options: chartOpt });
    }
    if (barEl) {
        new Chart(barEl, { type: 'bar', data: { labels: ['To Do', 'In Progress', 'Done'], datasets: [{ label: 'Tasks', data: @json(array_values($bar_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderRadius: 4 }] }, options: chartOpt });
    }
})();
</script>
@endpush