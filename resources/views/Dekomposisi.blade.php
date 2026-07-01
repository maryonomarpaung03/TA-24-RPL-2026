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
            radial-gradient(circle, #cbd5e1 1px, transparent 1px);
        background-size: 24px 24px;
        background-color: #f8fafc;
    }

    /* ── Node wrapper ── */
    .drawflow .drawflow-node.topic-node {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        min-width: 100px;
        max-width: 260px;
        padding: 0;
    }
    .drawflow .drawflow-node.topic-node.selected { background: transparent !important; border: none !important; box-shadow: none !important; outline: none !important; }
    .drawflow .drawflow-node.topic-node:focus,
    .drawflow .drawflow-node.topic-node:focus-visible { outline: none !important; box-shadow: none !important; }

    /* ── Inner shape base ── */
    .drawflow .drawflow-node.topic-node .topic-inner {
        box-sizing: border-box;
        box-shadow: 0 2px 10px rgba(0,0,0,0.10), 0 0 0 1.5px rgba(0,0,0,0.07) !important;
        transition: box-shadow 0.15s;
    }
    .drawflow .drawflow-node.topic-node:hover .topic-inner,
    .drawflow .drawflow-node.topic-node.selected .topic-inner {
        box-shadow: 0 4px 18px rgba(0,0,0,0.14), 0 0 0 2px rgba(59,130,246,0.45) !important;
    }

    /* ── Root node glow ── */
    .drawflow .drawflow-node.topic-node .topic-inner.is-root {
        box-shadow: 0 4px 24px rgba(59,130,246,0.22), 0 0 0 2.5px rgba(59,130,246,0.25) !important;
    }
    .drawflow .drawflow-node.topic-node:hover .topic-inner.is-root,
    .drawflow .drawflow-node.topic-node.selected .topic-inner.is-root {
        box-shadow: 0 6px 28px rgba(59,130,246,0.35), 0 0 0 3px rgba(59,130,246,0.55) !important;
    }

    /* ── Labels ── */
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
        line-height: 1.2;
    }

    /* ── Shapes ── */
    .drawflow .drawflow-node.topic-node .topic-inner.shape-square {
        border-radius: 8px;
        padding: 12px 16px;
        min-width: 80px;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rounded {
        border-radius: 18px;
        padding: 12px 18px;
        min-width: 80px;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-rectangle {
        border-radius: 12px;
        padding: 12px 18px;
        min-width: 180px;
        max-width: 260px;
        text-align: left;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-capsule {
        border-radius: 999px;
        padding: 10px 24px;
        min-width: 80px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-circle {
        border-radius: 50%;
        width: 140px;
        height: 140px;
        min-width: 140px;
        min-height: 140px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .drawflow .drawflow-node.topic-node .topic-inner.shape-circle .node-label {
        font-size: 12px;
        line-height: 1.3;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 5;
        -webkit-box-orient: vertical;
    }

    /* ── Connection lines ── */
    .drawflow .connection .main-path {
        stroke: #334155 !important;
        stroke-width: 2px;
        stroke-linecap: round;
        marker-end: url(#df-arrow);
    }
    .drawflow .connection .main-path:hover { stroke: #1e40af !important; stroke-width: 2.5px; marker-end: url(#df-arrow-blue); }
    .drawflow .connection.selected .main-path { stroke: #2563eb !important; stroke-width: 2.5px; marker-end: url(#df-arrow-blue); }

    /* ── Port dots ── */
    .drawflow .drawflow-node .input,
    .drawflow .drawflow-node .output {
        background: #fff;
        border: 2px solid #94a3b8;
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .drawflow .drawflow-node .input:hover,
    .drawflow .drawflow-node .output:hover {
        background: #3b82f6;
        border-color: #2563eb;
    }
</style>
@endpush

@section('content')
{{-- SVG arrow marker defs (referenced by CSS marker-end) --}}
<svg style="position:absolute;width:0;height:0;overflow:hidden;" aria-hidden="true">
    <defs>
        <marker id="df-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto" markerUnits="userSpaceOnUse">
            <polygon points="0 1, 8 4, 0 7" fill="#334155"/>
        </marker>
        <marker id="df-arrow-blue" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto" markerUnits="userSpaceOnUse">
            <polygon points="0 1, 8 4, 0 7" fill="#2563eb"/>
        </marker>
    </defs>
</svg>
<div class="w-full min-w-0" x-data="dekomposisiBoard(@js($diagramSeed), @js($user['initials'] ?? 'ME'), @js($user['name'] ?? ''), @js($id), @js(route('dekomposisi.sync', $id)), @js(route('dekomposisi.submit', $id)), @js(csrf_token()), @js($approvedProblems))">
    <div class="flex flex-col gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Projects / Dekomposisi</p>
                        <h1 class="text-3xl font-bold text-slate-900">Dekomposisi Masalah</h1>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[1.7fr_1fr] gap-6">
                        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                            <div class="border-b border-slate-200 px-5 py-2.5 flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">Draw Mode</span>
                                </div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    {{-- Zoom --}}
                                    <button @click="zoomIn()" title="Zoom In" class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200 transition">
                                        <i class="fas fa-search-plus text-[11px]"></i> Zoom In
                                    </button>
                                    <button @click="zoomOut()" title="Zoom Out" class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200 transition">
                                        <i class="fas fa-search-minus text-[11px]"></i> Zoom Out
                                    </button>
                                    <div class="w-px h-5 bg-slate-200 mx-0.5"></div>
                                    {{-- Connections --}}
                                    <button @click="removeLastConnection()" title="Hapus garis terakhir" class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 transition">
                                        <i class="fas fa-minus text-[11px]"></i> Hapus Garis
                                    </button>
                                    <button @click="clearAllConnections()" title="Hapus semua garis" class="flex items-center gap-1.5 rounded-lg bg-orange-50 px-3 py-1.5 text-xs font-medium text-orange-700 hover:bg-orange-100 transition">
                                        <i class="fas fa-times text-[11px]"></i> Hapus Semua
                                    </button>
                                    <div class="w-px h-5 bg-slate-200 mx-0.5"></div>
                                    {{-- Reset --}}
                                    <button @click="resetCanvas()" title="Reset canvas" class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition">
                                        <i class="fas fa-redo-alt text-[11px]"></i> Reset
                                    </button>
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

                            {{-- Tabel Ringkasan Diagram --}}
                            <div class="border-t border-slate-200 px-6 py-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-slate-400">Struktur Node Diagram</h3>
                                    <span class="text-xs text-slate-400" x-text="diagramTree.length + ' node'"></span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200">
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4 whitespace-nowrap">Nama Node</th>
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4 whitespace-nowrap">Tipe</th>
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4 whitespace-nowrap">Parent</th>
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4 whitespace-nowrap">Child</th>
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 pr-4 whitespace-nowrap">Pembuat</th>
                                                <th class="pb-3 text-left text-xs font-semibold text-slate-500 whitespace-nowrap">Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-if="diagramTree.length === 0">
                                                <tr>
                                                    <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                                        Diagram belum dibuat. Tambahkan topik untuk memulai.
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="node in diagramTree" :key="node.id">
                                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition align-top">
                                                    {{-- Nama Node --}}
                                                    <td class="py-3 pr-4">
                                                        <div class="flex items-start gap-2">
                                                            <div class="mt-1.5 h-2 w-2 rounded-full shrink-0"
                                                                 :style="'background:' + textColorForBackground(node.color)"></div>
                                                            <span class="font-semibold text-slate-800 leading-snug" x-text="node.title"></span>
                                                        </div>
                                                    </td>
                                                    {{-- Tipe --}}
                                                    <td class="py-3 pr-4">
                                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap"
                                                              :style="'background:' + node.color + ';color:' + textColorForBackground(node.color)">
                                                            <i class="fas fa-circle text-[5px]"></i>
                                                            <span x-text="node.type"></span>
                                                        </span>
                                                    </td>
                                                    {{-- Parent --}}
                                                    <td class="py-3 pr-4">
                                                        <template x-if="node.parents.length === 0">
                                                            <span class="text-slate-300 text-xs">—</span>
                                                        </template>
                                                        <template x-if="node.parents.length > 0">
                                                            <div class="flex flex-col gap-1">
                                                                <template x-for="p in node.parents" :key="p">
                                                                    <span class="inline-flex items-center gap-1 text-xs text-slate-600">
                                                                        <i class="fas fa-arrow-up text-[9px] text-slate-400"></i>
                                                                        <span x-text="p"></span>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </td>
                                                    {{-- Children --}}
                                                    <td class="py-3 pr-4">
                                                        <template x-if="node.children.length === 0">
                                                            <span class="text-slate-300 text-xs">—</span>
                                                        </template>
                                                        <template x-if="node.children.length > 0">
                                                            <div class="flex flex-col gap-1">
                                                                <template x-for="c in node.children" :key="c">
                                                                    <span class="inline-flex items-center gap-1 text-xs text-slate-600">
                                                                        <i class="fas fa-arrow-down text-[9px] text-slate-400"></i>
                                                                        <span x-text="c"></span>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </td>
                                                    {{-- Pembuat --}}
                                                    <td class="py-3 pr-4">
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="h-6 w-6 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-700"
                                                                 x-text="(node.createdBy || '?').slice(0,2).toUpperCase()"></div>
                                                            <span class="text-xs text-slate-600" x-text="node.createdBy || '—'"></span>
                                                        </div>
                                                    </td>
                                                    {{-- Tanggal --}}
                                                    <td class="py-3 text-xs text-slate-500 whitespace-nowrap" x-text="node.createdAt || '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <aside class="space-y-2">

                            {{-- STEP 1: Pilih Masalah --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">1</span>
                                    <h3 class="text-sm font-bold text-slate-800">Pilih Masalah</h3>
                                </div>
                                <p x-show="approvedProblems.length === 0" class="text-xs text-slate-400 text-center py-3">Belum ada masalah yang disetujui dosen.</p>
                                <div x-show="approvedProblems.length > 0" class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-900">
                                                <th class="pb-2 text-left text-xs font-semibold text-slate-700">No</th>
                                                <th class="pb-2 text-left text-xs font-semibold text-slate-700">Masalah</th>
                                                <th class="pb-2 text-xs font-semibold text-slate-700"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(problem, i) in approvedProblems" :key="problem.id">
                                                <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                                                    <td class="py-2.5 pr-2 text-xs text-slate-400 align-top" x-text="i + 1"></td>
                                                    <td class="py-2.5 pr-3 align-top">
                                                        <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="problem.title"></p>
                                                        <div class="flex gap-1 mt-1 flex-wrap">
                                                            <span x-show="problem.category" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700" x-text="problem.category"></span>
                                                            <span x-show="problem.priority" class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600" x-text="problem.priority"></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 align-top">
                                                        <button x-show="!addedProblemIds.includes(problem.id)" type="button" @click="addProblemToDiagram(problem)"
                                                                class="flex items-center gap-1 rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition whitespace-nowrap">
                                                            <i class="fas fa-plus text-[9px]"></i> Tambah
                                                        </button>
                                                        <span x-show="addedProblemIds.includes(problem.id)"
                                                              class="flex items-center gap-1 rounded-lg bg-green-100 px-2.5 py-1.5 text-xs font-semibold text-green-700 whitespace-nowrap">
                                                            <i class="fas fa-check text-[9px]"></i> Ditambahkan
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- connector --}}
                            <div class="flex justify-center py-1">
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-px h-3 bg-slate-300"></div>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </div>
                            </div>

                            {{-- STEP 2: Tambah Topik --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">2</span>
                                    <h3 class="text-sm font-bold text-slate-800">Tambah Topik</h3>
                                </div>
                                <button @click="showTopicModal = true" class="flex w-full items-center justify-between rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
                                    <span>+ Tambah Sub-Topik</span>
                                    <i class="fas fa-plus-circle text-base text-blue-600"></i>
                                </button>
                            </div>

                            {{-- connector --}}
                            <div class="flex justify-center py-1">
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-px h-3 bg-slate-300"></div>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </div>
                            </div>

                            {{-- STEP 3: Daftar Diagram --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">3</span>
                                    <h3 class="text-sm font-bold text-slate-800">Daftar Diagram</h3>
                                </div>
                                <div class="space-y-2">
                                    <template x-if="topicList.length === 0">
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-400 text-center">
                                            Belum ada topik di diagram.
                                        </div>
                                    </template>
                                    <template x-for="topic in topicList" :key="topic.id">
                                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2.5">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-700" x-text="topic.title"></p>
                                                <p class="text-[11px] text-slate-400" x-text="'oleh ' + (topic.createdBy || '-')"></p>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <button type="button" @click="openEditTopic(topic)" class="h-7 w-7 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition" title="Edit">
                                                    <i class="fas fa-pen text-[11px]"></i>
                                                </button>
                                                <button x-show="!topic.isRoot" @click="removeTopic(topic.id)" type="button" class="h-7 w-7 rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition" title="Hapus">
                                                    <i class="fas fa-trash text-[11px]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- connector --}}
                            <div class="flex justify-center py-1">
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-px h-3 bg-slate-300"></div>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </div>
                            </div>

                            {{-- STEP 4: Export --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">4</span>
                                    <h3 class="text-sm font-bold text-slate-800">Export</h3>
                                </div>
                                <button class="flex w-full items-center justify-between rounded-2xl bg-blue-50 border border-blue-200 px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">
                                    <span>Download Diagram (.PNG)</span>
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>

                            {{-- connector --}}
                            <div class="flex justify-center py-1">
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-px h-3 bg-slate-300"></div>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </div>
                            </div>

                            {{-- STEP 5: Kirim ke Dosen --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">5</span>
                                    <h3 class="text-sm font-bold text-slate-800">Kirim ke Dosen</h3>
                                </div>
                                <p class="text-xs text-slate-500 mb-3">Kirim diagram dekomposisi beserta history pembuatannya kepada dosen untuk ditinjau.</p>

                                <template x-if="submitSuccess">
                                    <div class="mb-3 rounded-xl bg-green-50 border border-green-200 px-4 py-2.5 text-xs font-semibold text-green-700 flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Diagram berhasil dikirim ke dosen!</span>
                                    </div>
                                </template>
                                <template x-if="submitError">
                                    <div class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-2.5 text-xs font-semibold text-red-600 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span x-text="submitError"></span>
                                    </div>
                                </template>

                                <button @click="kirimKeDosen()" :disabled="submitting"
                                        class="flex w-full items-center justify-between rounded-2xl bg-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed transition">
                                    <span x-text="submitting ? 'Mengirim...' : 'Kirim Diagram ke Dosen'"></span>
                                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                </button>
                            </div>

                            {{-- Komentar (di luar alur kerja) --}}
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-5 shadow-sm mt-2">
                                <div class="flex items-center gap-3 mb-4">
                                    <i class="fas fa-comments text-slate-400 text-sm"></i>
                                    <h3 class="text-sm font-bold text-slate-800">Komentar Tim</h3>
                                </div>
                                <div class="space-y-3 max-h-48 overflow-y-auto">
                                    <p x-show="comments.length === 0" class="text-xs text-slate-400 text-center py-2">Belum ada komentar.</p>
                                    <template x-for="comment in comments" :key="comment.id">
                                        <div class="rounded-2xl bg-slate-50 p-3 flex items-start gap-2.5">
                                            <div class="h-8 w-8 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700" x-text="comment.author"></div>
                                            <p class="text-sm text-slate-600 pt-1" x-text="comment.text"></p>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <input x-model="newComment" @keydown.enter.prevent="submitComment()" type="text" placeholder="Ketik komentar..."
                                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-400"/>
                                    <button @click="submitComment()" type="button" class="w-full rounded-2xl bg-blue-600 text-white py-2 text-sm font-semibold hover:bg-blue-700 transition">Kirim</button>
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
function dekomposisiBoard(seedData, userInitials, currentUserName, projectId, syncUrl, submitUrl, csrfToken, approvedProblemsData) {
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
        approvedProblems: approvedProblemsData || [],
        addedProblemIds: [],
        diagramTree: [],
        selectedNodeId: null,
        lastConnection: null,
        editor: null,
        nodeIdMap: {},
        seed: seedData,
        userInitials: userInitials || 'ME',
        currentUserName: String(currentUserName || '').trim(),
        projectId: projectId,
        syncUrl: String(syncUrl || ''),
        submitUrl: String(submitUrl || ''),
        csrfToken: String(csrfToken || ''),
        submitting: false,
        submitSuccess: false,
        submitError: null,
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
            const creator = this.escapeHtml(this.topicCreator(topic) || '');
            const isRoot = (topic.key === 'root');
            const rootClass = isRoot ? ' is-root' : '';
            const creatorHtml = creator
                ? `<div class="node-creator" style="color:${fg};">${creator}</div>`
                : '';
            return `<div class="${this.shapeClass(topic.shape)} topic-inner${rootClass}" style="background:${bg};">
                <div class="node-label" style="color:${fg};">${title}</div>
                ${creatorHtml}
            </div>`;
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
            // rebuild addedProblemIds
            const added = new Set();
            Object.values(all).forEach(node => {
                const m = String(node.data.key || '').match(/^prob-(\d+)-/);
                if (m) added.add(Number(m[1]));
            });
            this.addedProblemIds = Array.from(added);
            // build parent-child tree for summary table
            this.diagramTree = Object.keys(all).map(id => {
                const node = all[id];
                const title = node.data.title || node.name || '-';
                const isRoot = (node.data.key === 'root');
                // children: nodes this node outputs to
                const childIds = Object.values(node.outputs || {})
                    .flatMap(o => (o.connections || []).map(c => c.node));
                const children = childIds
                    .map(cid => all[cid] ? (all[cid].data.title || all[cid].name || '-') : null)
                    .filter(Boolean);
                // parents: nodes that output into this node
                const parentIds = Object.values(node.inputs || {})
                    .flatMap(i => (i.connections || []).map(c => c.node));
                const parents = parentIds
                    .map(pid => all[pid] ? (all[pid].data.title || all[pid].name || '-') : null)
                    .filter(Boolean);
                const type = (isRoot || children.length > 0) ? 'Parent' : 'Child';
                return {
                    id: Number(id),
                    title,
                    isRoot,
                    type,
                    parents,
                    children,
                    color: node.data.color || '#dbeafe',
                    createdBy: this.topicCreator(node.data),
                    createdAt: node.data.createdAt || '-',
                };
            });
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
        addProblemToDiagram(problem) {
            if (!this.editor) return;
            const key = `prob-${problem.id}-${Date.now()}`;
            const posX = 160 + (this.topicList.length % 3) * 220;
            const posY = 120 + Math.floor(this.topicList.length / 3) * 140;
            const payload = {
                key,
                title: problem.title,
                shape: 'rounded',
                color: '#dbeafe',
                createdBy: this.currentUserName || this.userInitials,
                createdAt: this.todayDate(),
            };
            this.editor.addNode(key, 1, 1, posX, posY, 'topic-node', payload, this.nodeHtml(payload));
            this.syncTopicList();
            this.persistDiagram();
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
        },
        async kirimKeDosen() {
            if (!this.submitUrl || this.submitting) return;
            if (!confirm('Apakah Anda yakin akan mengirimkan diagram dekomposisi ke dosen?')) return;
            this.submitting = true;
            this.submitSuccess = false;
            this.submitError = null;

            const payload = this.getPersistPayload();

            if (!payload.nodes || payload.nodes.length === 0) {
                this.submitError = 'Diagram masih kosong. Tambahkan topik terlebih dahulu.';
                this.submitting = false;
                return;
            }

            try {
                const res = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (json.ok) {
                    this.submitSuccess = true;
                } else {
                    this.submitError = json.message || 'Gagal mengirim diagram.';
                }
            } catch (e) {
                this.submitError = 'Terjadi kesalahan koneksi. Coba lagi.';
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush