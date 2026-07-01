@extends('layouts.app')

@section('title', 'Dekomposisi Masalah - ' . $project->title . ' - DELPRO')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/drawflow/dist/drawflow.min.css">
<style>
    [x-cloak] { display: none !important; }
    #drawflow-dosen {
        width: 100%;
        height: 520px;
        background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
        background-size: 24px 24px;
        background-color: #f8fafc;
        pointer-events: none;
    }
    #drawflow-dosen .drawflow .drawflow-node.topic-node {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        min-width: 100px;
        max-width: 260px;
        padding: 0;
    }
    .drawflow .drawflow-node.topic-node .topic-inner {
        box-sizing: border-box;
        box-shadow: 0 2px 10px rgba(0,0,0,0.10), 0 0 0 1.5px rgba(0,0,0,0.07) !important;
    }
    .drawflow .drawflow-node.topic-node .node-label {
        font-size: 13px;
        line-height: 1.4;
        font-weight: 700;
    }
    .drawflow .drawflow-node.topic-node .node-creator {
        font-size: 10px;
        font-weight: 500;
        margin-top: 3px;
        opacity: 0.6;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-square { border-radius: 8px; padding: 12px 16px; min-width: 80px; text-align: center; }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rounded { border-radius: 18px; padding: 12px 18px; min-width: 80px; text-align: center; }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rectangle { border-radius: 12px; padding: 12px 18px; min-width: 180px; max-width: 260px; text-align: left; }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-capsule { border-radius: 999px; padding: 10px 24px; min-width: 80px; text-align: center; display: inline-flex; align-items: center; justify-content: center; }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-circle { border-radius: 50%; width: 140px; height: 140px; min-width: 140px; min-height: 140px; padding: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .drawflow .connection .main-path { stroke: #334155 !important; stroke-width: 2px; stroke-linecap: round; }
    .drawflow .drawflow-node .input, .drawflow .drawflow-node .output { background: #fff; border: 2px solid #94a3b8; width: 12px; height: 12px; border-radius: 50%; }
</style>
@endpush

@section('content')
<div class="w-full space-y-6" x-data="dosenDekomposisi(@js($submissions))">

    <a href="{{ route('dosen.proyek-mahasiswa.show', $project->id) }}" class="text-blue-600 text-xs font-bold hover:underline inline-block">
        &larr; Kembali ke detail proyek
    </a>

    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <p class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-1">Problem Decomposition</p>
        <h1 class="text-2xl font-bold text-slate-900">{{ $project->title }}</h1>
        <p class="text-sm text-slate-500 mt-1">Diagram dekomposisi masalah yang dikirim oleh tim mahasiswa</p>
    </div>

    {{-- Tidak ada submission --}}
    <template x-if="submissions.length === 0">
        <div class="bg-white rounded-3xl border border-slate-200 p-10 text-center shadow-sm">
            <i class="fas fa-project-diagram text-4xl text-slate-300 mb-4"></i>
            <p class="text-slate-500 font-semibold">Belum ada diagram yang dikirim</p>
            <p class="text-xs text-slate-400 mt-1">Tim mahasiswa belum mengirimkan diagram dekomposisi masalah.</p>
        </div>
    </template>

    {{-- List submission --}}
    <template x-if="submissions.length > 0">
        <div class="space-y-4">

            {{-- Tabs pilih submission --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Riwayat Pengiriman</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(s, i) in submissions" :key="s.id">
                        <button @click="activeIdx = i"
                                :class="activeIdx === i
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="rounded-xl border px-4 py-2 text-xs font-semibold transition">
                            <span x-text="'Pengiriman #' + (submissions.length - i)"></span>
                            <span class="ml-1 opacity-70" x-text="formatDate(s.submitted_at)"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Detail submission aktif --}}
            <template x-if="activeSubmission">
                <div class="space-y-4">

                    {{-- Info pengiriman --}}
                    <div class="bg-white rounded-2xl border border-slate-200 px-6 py-4 shadow-sm flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700"
                                 x-text="(activeSubmission.submitter || '?').slice(0,2).toUpperCase()"></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800" x-text="activeSubmission.submitter"></p>
                                <p class="text-xs text-slate-400" x-text="formatDate(activeSubmission.submitted_at)"></p>
                            </div>
                        </div>
                        <span class="ml-auto rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                            <i class="fas fa-check-circle mr-1"></i> Terkirim
                        </span>
                    </div>

                    {{-- Canvas diagram (read-only) --}}
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="border-b border-slate-200 px-5 py-3 flex items-center gap-2">
                            <i class="fas fa-project-diagram text-slate-400 text-sm"></i>
                            <span class="text-sm font-bold text-slate-700">Diagram Dekomposisi</span>
                            <span class="ml-2 text-xs text-slate-400">(mode baca)</span>
                        </div>
                        <div class="relative overflow-hidden min-h-[520px]">
                            <div :id="'drawflow-dosen'" x-ref="drawflowContainer" style="width:100%;height:520px;background-image:radial-gradient(circle,#cbd5e1 1px,transparent 1px);background-size:24px 24px;background-color:#f8fafc;pointer-events:none;"></div>
                        </div>
                    </div>

                    {{-- Tabel struktur node --}}
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-slate-400 mb-4">Struktur Node Diagram</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4">Nama Node</th>
                                        <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4">Parent</th>
                                        <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4">Child</th>
                                        <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4">Pembuat</th>
                                        <th class="pb-3 text-left text-xs font-semibold text-slate-500">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="node in buildTree(activeSubmission)" :key="node.key">
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition align-top">
                                            <td class="py-3 pr-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-2.5 w-2.5 rounded-full shrink-0" :style="'background:' + node.color"></div>
                                                    <span class="font-semibold text-slate-800" x-text="node.title"></span>
                                                </div>
                                            </td>
                                            <td class="py-3 pr-4 text-xs text-slate-500" x-text="node.parents.join(', ') || '—'"></td>
                                            <td class="py-3 pr-4 text-xs text-slate-500" x-text="node.children.join(', ') || '—'"></td>
                                            <td class="py-3 pr-4 text-xs text-slate-600" x-text="node.createdBy || '—'"></td>
                                            <td class="py-3 text-xs text-slate-400 whitespace-nowrap" x-text="node.createdAt || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Komentar tim --}}
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-800 mb-4">
                            <i class="fas fa-comments text-slate-400 mr-2"></i>Komentar Tim
                        </h3>
                        <template x-if="!activeSubmission.comments || activeSubmission.comments.length === 0">
                            <p class="text-xs text-slate-400 text-center py-4">Tidak ada komentar pada pengiriman ini.</p>
                        </template>
                        <div class="space-y-3">
                            <template x-for="comment in (activeSubmission.comments || [])" :key="comment.id">
                                <div class="rounded-2xl bg-slate-50 p-3 flex items-start gap-2.5">
                                    <div class="h-8 w-8 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700" x-text="comment.author"></div>
                                    <p class="text-sm text-slate-600 pt-1" x-text="comment.text"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/drawflow/dist/drawflow.min.js"></script>
<script>
function dosenDekomposisi(submissions) {
    return {
        submissions: submissions || [],
        activeIdx: 0,
        editor: null,

        get activeSubmission() {
            return this.submissions[this.activeIdx] || null;
        },

        init() {
            this.$nextTick(() => this.renderDiagram());
            this.$watch('activeIdx', () => {
                this.$nextTick(() => this.renderDiagram());
            });
        },

        formatDate(dt) {
            if (!dt) return '-';
            const d = new Date(dt);
            if (isNaN(d)) return dt;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        sanitizeHex(c) {
            const s = String(c ?? '').trim();
            return /^#[0-9a-fA-F]{6}$/.test(s) ? s : '#dbeafe';
        },

        textColor(hex) {
            const h = this.sanitizeHex(hex).slice(1);
            const r = parseInt(h.slice(0,2),16);
            const g = parseInt(h.slice(2,4),16);
            const b = parseInt(h.slice(4,6),16);
            return (0.299*r+0.587*g+0.114*b)/255 > 0.55 ? '#0f172a' : '#f8fafc';
        },

        nodeHtml(node) {
            const bg = this.sanitizeHex(node.color);
            const fg = this.textColor(bg);
            const title = String(node.title ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const creator = String(node.createdBy ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const isRoot = node.key === 'root';
            const shapeClass = 'shape-' + (node.shape || 'rounded');
            const rootClass = isRoot ? ' is-root' : '';
            return `<div class="${shapeClass} topic-inner${rootClass}" style="background:${bg};">
                <div class="node-label" style="color:${fg};">${title}</div>
                ${creator ? `<div class="node-creator" style="color:${fg};">${creator}</div>` : ''}
            </div>`;
        },

        renderDiagram() {
            const sub = this.activeSubmission;
            const container = this.$refs.drawflowContainer;
            if (!container) return;

            // Destroy previous editor
            if (this.editor) {
                try { this.editor.clear(); } catch(e) {}
                container.innerHTML = '';
                this.editor = null;
            }

            if (!sub || !sub.nodes || sub.nodes.length === 0) return;

            this.editor = new Drawflow(container);
            this.editor.reroute = true;
            this.editor.start();
            this.editor.editor_mode = 'view';

            const nodeIdMap = {};
            sub.nodes.forEach(node => {
                const newId = this.editor.addNode(
                    node.key, 1, 1,
                    node.x || 100, node.y || 100,
                    'topic-node', node,
                    this.nodeHtml(node)
                );
                nodeIdMap[node.key] = newId;
            });

            (sub.connections || []).forEach(conn => {
                const fromId = nodeIdMap[conn.from];
                const toId   = nodeIdMap[conn.to];
                if (fromId && toId) {
                    this.editor.addConnection(fromId, toId, 'output_1', 'input_1');
                }
            });
        },

        buildTree(sub) {
            if (!sub || !sub.nodes) return [];
            const nodes = sub.nodes;
            const conns = sub.connections || [];
            return nodes.map(node => {
                const children = conns
                    .filter(c => c.from === node.key)
                    .map(c => {
                        const n = nodes.find(n => n.key === c.to);
                        return n ? n.title : c.to;
                    });
                const parents = conns
                    .filter(c => c.to === node.key)
                    .map(c => {
                        const n = nodes.find(n => n.key === c.from);
                        return n ? n.title : c.from;
                    });
                return {
                    key: node.key,
                    title: node.title,
                    color: node.color || '#dbeafe',
                    createdBy: node.createdBy || '-',
                    createdAt: node.createdAt || '-',
                    parents,
                    children,
                };
            });
        },
    };
}
</script>
@endpush
