@extends('layouts.app')

@section('title', 'Dekomposisi Masalah - DELPRO')
@section('root_data', '{ sidebarOpen: true }')
@section('main_class', 'flex-1 flex flex-col min-w-0 overflow-y-auto')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/drawflow/dist/drawflow.min.css">
<style>
    [x-cloak] { display: none !important; }
    #drawflow {
        width: 100%;
        height: 720px;
        background-image:
            linear-gradient(to right, rgba(148, 163, 184, 0.14) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(148, 163, 184, 0.14) 1px, transparent 1px);
        background-size: 20px 20px;
        background-color: #fff;
    }
    /* Topik: isi bentuk pakai warna dari pemilih (background tulisan); node luar tetap transparan */
    .drawflow .drawflow-node.topic-node {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        min-width: 120px;
        max-width: 280px;
        padding: 4px 0;
    }
    /* Tanpa ring/fokus biru di luar kotak topik (drawflow + selection) */
    .drawflow .drawflow-node.topic-node.selected {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
    }
    .drawflow .drawflow-node.topic-node:focus,
    .drawflow .drawflow-node.topic-node:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }
    .drawflow .drawflow-node.topic-node .topic-inner {
        box-shadow: none !important;
        box-sizing: border-box;
    }
    .drawflow .drawflow-node.topic-node .node-label {
        font-size: 12px;
        line-height: 1.35;
        font-weight: 600;
    }
    /* Bentuk topik — background teks dari inline style nodeHtml */
    .drawflow .drawflow-node.topic-node .topic-inner.shape-square {
        border-radius: 0;
        padding: 10px 14px;
        min-width: 72px;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rounded {
        border-radius: 16px;
        padding: 10px 16px;
        min-width: 72px;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rectangle {
        border-radius: 8px;
        padding: 10px 16px;
        min-width: 200px;
        max-width: 280px;
        text-align: left;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-capsule {
        border-radius: 999px;
        padding: 10px 22px;
        min-width: 72px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-circle {
        border-radius: 50%;
        width: 132px;
        height: 132px;
        min-width: 132px;
        min-height: 132px;
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-circle .node-label {
        font-size: 11px;
        line-height: 1.25;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 7;
        -webkit-box-orient: vertical;
    }
    /*
     * Drawflow default memakai stroke biru untuk jalur koneksi antar node.
     * Override ke slate agar selaras dengan palet halaman (bukan bug — memang bawaan library).
     */
    .drawflow .connection .main-path {
        stroke: #94a3b8 !important;
        stroke-width: 2px;
    }
    .drawflow .connection .main-path:hover {
        stroke: #64748b !important;
    }
    .drawflow .connection.selected .main-path {
        stroke: #475569 !important;
    }
    .drawflow .drawflow-node .input,
    .drawflow .drawflow-node .output {
        background: #cbd5e1;
        border-color: #94a3b8;
    }
    .drawflow .drawflow-node .input:hover,
    .drawflow .drawflow-node .output:hover {
        background: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="w-full min-w-0" x-data="dekomposisiBoard(@js($diagramSeed), @js($user['initials'] ?? 'ME'), @js($user['name'] ?? ''), @js($id), @js(route('dekomposisi.sync', $id)), @js(csrf_token()))">
    <div class="flex flex-col gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Projects / Dekomposisi</p>
                        <h1 class="text-3xl font-bold text-slate-900">Dekomposisi Masalah</h1>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[1.7fr_1fr] gap-6">
                        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                            <div class="border-b border-slate-200 px-5 py-3 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 font-semibold">Draw Mode</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="h-8 w-8 rounded-md bg-slate-100 text-slate-500"><i class="fas fa-mouse-pointer text-xs"></i></button>
                                    <button class="h-8 w-8 rounded-md bg-slate-100 text-slate-500"><i class="fas fa-vector-square text-xs"></i></button>
                                    <button class="h-8 w-8 rounded-md bg-slate-100 text-slate-500"><i class="fas fa-circle text-xs"></i></button>
                                    <button class="h-8 w-8 rounded-md bg-slate-100 text-slate-500"><i class="fas fa-grip-lines text-xs"></i></button>
                                </div>
                            </div>

                            <div class="relative overflow-hidden min-h-[720px]">
                                <div class="absolute top-4 right-4 flex -space-x-2 z-20">
                                    @foreach(['DS','NT','RH'] as $member)
                                    <div class="h-7 w-7 rounded-full bg-blue-100 border border-white flex items-center justify-center text-[10px] font-bold text-blue-700 shadow-sm">{{ $member }}</div>
                                    @endforeach
                                </div>

                                <div id="drawflow" x-ref="drawflow"></div>
                            </div>
                        </div>

                        <aside class="space-y-6">
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Tambah Topik</h3>
                                <button @click="showTopicModal = true" class="flex w-full items-center justify-between rounded-3xl bg-slate-100 px-4 py-4 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
                                    <span>Tambah Topik</span>
                                    <i class="fas fa-plus-circle text-lg"></i>
                                </button>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Daftar Diagram</h3>
                                <div class="space-y-3">
                                    <template x-if="topicList.length === 0">
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                            Belum ada topik.
                                        </div>
                                    </template>

                                    <template x-for="topic in topicList" :key="topic.id">
                                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-700" x-text="topic.title"></p>
                                                <p class="text-[11px] text-slate-500" x-text="'Dibuat oleh: ' + (topic.createdBy || '-')"></p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <button
                                                    type="button"
                                                    @click="openEditTopic(topic)"
                                                    class="h-7 w-7 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                                    title="Edit topik"
                                                >
                                                    <i class="fas fa-pen text-[11px]"></i>
                                                </button>
                                                <button
                                                    x-show="!topic.isRoot"
                                                    @click="removeTopic(topic.id)"
                                                    type="button"
                                                    class="h-7 w-7 rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition"
                                                    title="Hapus topik"
                                                >
                                                    <i class="fas fa-trash text-[11px]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Zoom & Export</h3>
                                <div class="space-y-4 text-sm text-slate-700">
                                    <button @click="zoomIn()" class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Zoom In</span>
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                    <button @click="zoomOut()" class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Zoom Out</span>
                                        <i class="fas fa-search-minus"></i>
                                    </button>
                                    <button class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Download .PNG</span>
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                                <button @click="resetCanvas()" class="mt-3 w-full rounded-2xl bg-red-50 text-red-600 text-xs font-semibold px-4 py-2 hover:bg-red-100 transition">Reset Canvas</button>
                                <button @click="removeLastConnection()" class="mt-2 w-full rounded-2xl bg-amber-50 text-amber-700 text-xs font-semibold px-4 py-2 hover:bg-amber-100 transition">Hapus Garis Terakhir</button>
                                <button @click="clearAllConnections()" class="mt-2 w-full rounded-2xl bg-orange-50 text-orange-700 text-xs font-semibold px-4 py-2 hover:bg-orange-100 transition">Hapus Semua Garis</button>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Komentar</h3>
                                <div class="space-y-4">
                                    <template x-for="comment in comments" :key="comment.id">
                                        <div class="rounded-3xl bg-slate-50 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700" x-text="comment.author"></div>
                                                <div>
                                                    <p class="text-xs font-semibold text-slate-900" x-text="comment.author"></p>
                                                    <p class="text-sm text-slate-600" x-text="comment.text"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <input
                                        x-model="newComment"
                                        @keydown.enter.prevent="submitComment()"
                                        type="text"
                                        placeholder="Ketik komentar..."
                                        class="w-full rounded-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <button @click="submitComment()" type="button" class="w-full rounded-full bg-blue-600 text-white py-2.5 text-sm font-semibold hover:bg-blue-700 transition">
                                        Submit
                                    </button>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
    <div x-show="showTopicModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-3xl p-8 shadow-2xl" @click.outside="showTopicModal = false">
            <h3 class="text-xl font-bold text-slate-900 mb-6">Tambah Topik Dekomposisi</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Topik</label>
                    <textarea x-model="newTopicText" rows="4" placeholder="Contoh: Kesalahan input data karena validasi belum real-time." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Warna Topik</label>
                    <input type="color" x-model="newTopicColor" class="w-full h-12 rounded-xl border border-slate-200 bg-white px-2 py-2 cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Bentuk Topik</label>
                    <select x-model="newTopicShape" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400">
                        <option value="circle">Lingkaran</option>
                        <option value="square">Persegi</option>
                        <option value="rectangle">Persegi Panjang</option>
                        <option value="rounded">Petak Membulat</option>
                        <option value="capsule">Kapsul</option>
                    </select>
                </div>
            </div>
            <div class="mt-7 flex justify-end gap-3">
                <button type="button" @click="showTopicModal = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Batal</button>
                <button type="button" @click="addTopic()" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition">Tambah</button>
            </div>
        </div>
    </div>

    <div x-show="showEditTopicModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-lg bg-white rounded-3xl p-8 shadow-2xl" @click.outside="showEditTopicModal = false">
            <h3 class="text-xl font-bold text-slate-900 mb-6">Edit Topik</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Topik</label>
                    <textarea x-model="editTopicText" rows="4" placeholder="Ubah teks topik..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Warna Topik</label>
                    <input type="color" x-model="editTopicColor" class="w-full h-12 rounded-xl border border-slate-200 bg-white px-2 py-2 cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Bentuk Topik</label>
                    <select x-model="editTopicShape" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400">
                        <option value="circle">Lingkaran</option>
                        <option value="square">Persegi</option>
                        <option value="rectangle">Persegi Panjang</option>
                        <option value="rounded">Petak Membulat</option>
                        <option value="capsule">Kapsul</option>
                    </select>
                </div>
            </div>
            <div class="mt-7 flex justify-end gap-3">
                <button type="button" @click="showEditTopicModal = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Batal</button>
                <button type="button" @click="saveEditedTopic()" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/drawflow/dist/drawflow.min.js"></script>
<script>
function dekomposisiBoard(seedData, userInitials, currentUserName, projectId, syncUrl, csrfToken) {
    return {
        showTopicModal: false,
        showEditTopicModal: false,
        editTopicId: null,
        editTopicText: '',
        editTopicColor: '#dbeafe',
        editTopicShape: 'rounded',
        newTopicText: '',
        newTopicColor: '#dbeafe',
        newTopicShape: 'rounded',
        newComment: '',
        comments: seedData.comments || [],
        topicList: [],
        selectedNodeId: null,
        lastConnection: null,
        editor: null,
        nodeIdMap: {},
        seed: seedData,
        userInitials: userInitials || 'ME',
        currentUserName: String(currentUserName || '').trim(),
        projectId: projectId,
        syncUrl: String(syncUrl || ''),
        csrfToken: String(csrfToken || ''),
        init() {
            this.$nextTick(() => this.setupDrawflow());
        },
        shapeClass(shape) {
            return 'shape-' + (shape || 'rounded');
        },
        escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
        sanitizeHexColor(c) {
            const s = String(c ?? '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(s)) return s;
            return '#dbeafe';
        },
        textColorForBackground(hex) {
            const h = this.sanitizeHexColor(hex).slice(1);
            const r = parseInt(h.slice(0, 2), 16);
            const g = parseInt(h.slice(2, 4), 16);
            const b = parseInt(h.slice(4, 6), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.55 ? '#0f172a' : '#f8fafc';
        },
        topicCreator(topic) {
            const raw = topic.createdBy ?? topic.created_by ?? '';
            const s = String(raw).trim();
            return s || '';
        },
        topicCreatedAt(topic) {
            const raw = topic.createdAt ?? topic.created_at ?? '';
            const s = String(raw).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
            return this.todayDate();
        },
        todayDate() {
            const now = new Date();
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },
        topicHoverTitle(topic) {
            const who = this.topicCreator(topic);
            if (!who) return '';
            return 'Dibuat oleh: ' + who;
        },
        nodeHtml(topic) {
            const bg = this.sanitizeHexColor(topic.color);
            const fg = this.textColorForBackground(bg);
            const title = this.escapeHtml(topic.title);
            const ring = 'rgba(15,23,42,0.12)';
            const tip = this.topicHoverTitle(topic);
            const tipAttr = tip ? ` title="${this.escapeHtml(tip)}"` : '';
            return `<div class="${this.shapeClass(topic.shape)} topic-inner"${tipAttr} style="border:1px solid ${ring};background:${bg};"><div class="node-label" style="color:${fg};">${title}</div></div>`;
        },
        setupDrawflow() {
            this.editor = new Drawflow(this.$refs.drawflow);
            this.editor.reroute = true;
            this.editor.start();
            this.editor.zoom = 1;

            this.seed.nodes.forEach((topic) => {
                const inputPorts = 1;
                const outputPorts = 1;
                const topicData = {
                    key: topic.key,
                    title: topic.title,
                    shape: topic.shape,
                    color: topic.color,
                    createdBy: this.topicCreator(topic) || this.currentUserName || this.userInitials,
                    createdAt: this.topicCreatedAt(topic),
                };
                const newId = this.editor.addNode(
                    topic.key,
                    inputPorts,
                    outputPorts,
                    topic.x,
                    topic.y,
                    'topic-node',
                    topicData,
                    this.nodeHtml(topicData)
                );
                this.nodeIdMap[topic.key] = newId;
            });

            this.seed.connections.forEach((line) => {
                const fromId = this.nodeIdMap[line.from];
                const toId = this.nodeIdMap[line.to];
                if (fromId && toId) this.editor.addConnection(fromId, toId, 'output_1', 'input_1');
            });

            this.syncTopicList();
            this.editor.on('connectionCreated', (conn) => {
                this.lastConnection = conn;
                this.syncTopicList();
            });
            this.editor.on('connectionRemoved', () => this.syncTopicList());
            this.editor.on('nodeRemoved', () => this.syncTopicList());
        },
        getPersistPayload() {
            if (!this.editor) return { nodes: [], connections: [] };
            const data = this.editor.export().drawflow.Home.data || {};
            const nodes = Object.keys(data).map((id) => {
                const node = data[id];
                return {
                    key: node.data?.key || node.name || `node-${id}`,
                    title: node.data?.title || node.name || '',
                    shape: node.data?.shape || 'rounded',
                    color: this.sanitizeHexColor(node.data?.color || '#dbeafe'),
                    createdBy: this.topicCreator(node.data || {}),
                    createdAt: this.topicCreatedAt(node.data || {}),
                    x: Number(node.pos_x || 0),
                    y: Number(node.pos_y || 0),
                };
            });
            const connectionsMap = new Map();
            Object.keys(data).forEach((id) => {
                const node = data[id];
                const fromKey = node.data?.key || node.name;
                Object.keys(node.outputs || {}).forEach((outKey) => {
                    const list = node.outputs[outKey].connections || [];
                    list.forEach((conn) => {
                        const toNode = data[String(conn.node)];
                        const toKey = toNode?.data?.key || toNode?.name;
                        if (!fromKey || !toKey) return;
                        const uniq = `${fromKey}__${toKey}`;
                        if (!connectionsMap.has(uniq)) {
                            connectionsMap.set(uniq, { from: fromKey, to: toKey });
                        }
                    });
                });
            });
            return { nodes,

                connections:
                    Array.from(
                        connectionsMap.values()
                    ),

                comments:
                    this.comments
                    || []};
            },
        async persistDiagram() {
            if (!this.syncUrl || !this.csrfToken) return;
            const payload = this.getPersistPayload();
            try {
                await fetch(this.syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                console.error('Gagal sinkronisasi dekomposisi:', e);
            }
        },
        syncTopicList() {
            const all = this.editor.export().drawflow.Home.data;
            this.topicList = Object.keys(all).map((id) => ({
                id: Number(id),
                key: all[id].data.key,
                title: all[id].data.title || all[id].name,
                shape: all[id].data.shape || 'rounded',
                color: all[id].data.color || '#dbeafe',
                createdBy: this.topicCreator(all[id].data),
                isRoot: all[id].data.key === 'root',
            }));
            this.persistDiagram();
        },
        openEditTopic(topic) {
            if (!this.editor) return;
            this.editTopicId = topic.id;
            this.editTopicText = topic.title || '';
            this.editTopicColor = this.sanitizeHexColor(topic.color);
            this.editTopicShape = topic.shape || 'rounded';
            this.showEditTopicModal = true;
        },
        saveEditedTopic() {
            if (!this.editTopicText.trim() || !this.editor || this.editTopicId == null) return;
            const id = this.editTopicId;
            const entry = this.editor.getNodeFromId(id);
            if (!entry || !entry.data) return;
            const key = entry.data.key;
            const prevBy = this.topicCreator(entry.data);
            const updated = {
                ...entry.data,
                key,
                title: this.editTopicText.trim(),
                shape: this.editTopicShape,
                color: this.sanitizeHexColor(this.editTopicColor),
                createdBy: prevBy || this.currentUserName || this.userInitials,
                createdAt: this.topicCreatedAt(entry.data),
            };
            const newHtml = this.nodeHtml(updated);
            const mod = this.editor.getModuleFromNodeId(id);
            const store = this.editor.drawflow.drawflow[mod].data[id];
            store.data = updated;
            store.html = newHtml;
            const el = this.editor.container.querySelector('#node-' + id + ' .drawflow_content_node');
            if (el) el.innerHTML = newHtml;
            this.editor.updateConnectionNodes('node-' + id);
            this.syncTopicList();
            this.showEditTopicModal = false;
            this.editTopicId = null;
        },
        removeTopic(topicId) {

            if (!this.editor) {
                return;
            }

            this.editor.removeNodeId(
                `node-${topicId}`
            );

            this.syncTopicList();

            this.persistDiagram();
        },
        submitComment() {

            if (
                !this.newComment.trim()
            ) {
                return;
            }

            this.comments.push({

                id:
                    Date.now(),

                author:
                    this.userInitials,

                text:
                    this.newComment.trim()
            });

            this.newComment = '';

            /*
            autosave
            */
            this.persistDiagram();
        },
        addTopic() {
            if (!this.newTopicText.trim() || !this.editor) return;
            const key = `n-${Date.now()}`;
            const posX = 120 + (this.topicList.length % 4) * 180;
            const posY = 90 + Math.floor(this.topicList.length / 4) * 120;
            const creator = this.currentUserName || this.userInitials;
            const payload = {
                key,
                title: this.newTopicText.trim(),
                shape: this.newTopicShape,
                color: this.newTopicColor,
                createdBy: creator,
                createdAt: this.todayDate(),
            };
            this.editor.addNode(
                key,
                1,
                1,
                posX,
                posY,
                'topic-node',
                payload,
                this.nodeHtml(payload)
            );
            this.syncTopicList();
            this.newTopicText = '';
            this.newTopicColor = '#dbeafe';
            this.newTopicShape = 'rounded';
            this.showTopicModal = false;
        },
        zoomIn() {
            if (this.editor) this.editor.zoom_in();
        },
        zoomOut() {
            if (this.editor) this.editor.zoom_out();
        },
        resetCanvas() {
            if (!this.editor) return;
            this.editor.clear();
            this.nodeIdMap = {};
            this.lastConnection = null;
            this.setupDrawflow();
        },
        removeLastConnection() {
            if (!this.editor || !this.lastConnection) return;
            const c = this.lastConnection;
            if (c.output_id && c.input_id && c.output_class && c.input_class) {
                this.editor.removeSingleConnection(c.output_id, c.input_id, c.output_class, c.input_class);
            }
            this.lastConnection = null;
            this.syncTopicList();
        },
        clearAllConnections() {
            if (!this.editor) return;
            const data = this.editor.export().drawflow.Home.data || {};
            Object.keys(data).forEach((id) => {
                const node = data[id];
                Object.keys(node.outputs || {}).forEach((outKey) => {
                    const connections = node.outputs[outKey].connections || [];
                    connections.forEach((conn) => {
                        this.editor.removeSingleConnection(Number(id), conn.node, outKey, conn.output);
                    });
                });
            });
            this.lastConnection = null;
            this.syncTopicList();
        }
    };
}
</script>
@endpush