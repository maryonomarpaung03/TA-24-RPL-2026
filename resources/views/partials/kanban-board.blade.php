@php
    $editable = $editable ?? false;
    $board = $kanban ?? [];
@endphp

<div class="flex gap-6 overflow-x-auto pb-4">
    @foreach($board as $col)
    <div class="bg-gray-200/80 rounded-[2rem] p-6 flex flex-col h-[600px] w-80 shrink-0">
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center space-x-2 min-w-0">
                <div class="w-3 h-3 rounded-full bg-{{ $col['color'] }} shrink-0"></div>
                <h4 class="text-sm font-bold text-gray-700 truncate">{{ $col['label'] }}</h4>
                <span class="text-[10px] font-bold text-gray-400 bg-white rounded-full px-2 py-0.5">{{ count($col['tasks']) }}</span>
            </div>
            @if($editable)
            <div class="flex items-center gap-1 shrink-0">
                <button type="button"
                        @click="editCol = { id: {{ $col['id'] }}, label: @js($col['label']), color: '{{ $col['color'] }}', description: @js($col['description'] ?? ''), is_done: {{ $col['is_done'] ? 'true' : 'false' }}, requires_approval: {{ $col['requires_approval'] ? 'true' : 'false' }}, checklist: @js(!empty($col['checklist']) ? $col['checklist'] : ['']) }; colEditModal = true"
                        class="text-gray-400 hover:text-blue-500 transition p-1" title="Ubah kolom">
                    <i class="fas fa-pen text-[11px]"></i>
                </button>
                <button type="button"
                        @click="delCol = { id: {{ $col['id'] }}, label: @js($col['label']) }; colDeleteModal = true"
                        class="text-gray-400 hover:text-red-500 transition p-1" title="Hapus kolom">
                    <i class="fas fa-trash text-[11px]"></i>
                </button>
            </div>
            @endif
        </div>

        {{-- Indikator konfigurasi kolom --}}
        @if(($col['is_done'] ?? false) || ($col['requires_approval'] ?? false) || !empty($col['checklist']))
        <div class="flex flex-wrap items-center gap-1.5 mb-3">
            @if($col['is_done'] ?? false)
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700"><i class="fas fa-check mr-0.5"></i>Selesai</span>
            @endif
            @if($col['requires_approval'] ?? false)
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700"><i class="fas fa-user-shield mr-0.5"></i>Approval Dosen</span>
            @endif
            @if(!empty($col['checklist']))
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"><i class="fas fa-list-check mr-0.5"></i>{{ count($col['checklist']) }} checklist</span>
            @endif
        </div>
        @endif

        @if(!empty($col['description']))
        <p class="text-[10px] text-gray-500 leading-snug mb-4 -mt-1">{{ $col['description'] }}</p>
        @endif

        <div class="flex-1 space-y-4 overflow-y-auto">
            @if(empty($col['tasks']))
            <p class="text-[11px] text-gray-400 italic text-center py-8">Belum ada tugas.</p>
            @endif
            @foreach($col['tasks'] as $task)
            @php $isMine = isset($currentUserId) && (int) $task['assigned_to'] === (int) $currentUserId; @endphp
            <div class="bg-white rounded-2xl p-4 shadow-sm relative group transition hover:shadow-md"
                 x-show="typeof taskMatch === 'undefined' ? true : taskMatch({{ (int) ($task['assigned_to'] ?? 0) }}, {{ $task['days_left'] === null ? 'null' : (int) $task['days_left'] }})">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <p class="text-[11px] font-bold text-gray-800 leading-tight">{{ $task['name'] }}</p>
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($isMine)
                        <span class="text-[8px] px-1.5 py-0.5 rounded-full font-bold bg-blue-100 text-blue-600">Saya</span>
                        @endif
                        <button type="button"
                                @click="commentTask = { id: {{ $task['id'] }}, name: @js($task['name']), comments: @js($task['comments'] ?? []) }; commentModal = true"
                                class="relative text-gray-300 hover:text-blue-500 transition" title="Komentar tugas">
                            <i class="fas fa-comment-dots text-xs"></i>
                            @if(!empty($task['comments']))
                            <span class="absolute -top-1.5 -right-1.5 inline-flex min-w-[13px] h-3.5 items-center justify-center rounded-full bg-blue-600 px-1 text-[8px] font-bold text-white">{{ count($task['comments']) }}</span>
                            @endif
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-[8px] flex items-center justify-center font-bold text-blue-600 border border-white" title="{{ $task['assignee'] }}">{{ $task['creator'] }}</div>
                        <span class="text-[8px] px-2 py-0.5 rounded-full font-bold text-white {{ $task['level'] == 'Sulit' ? 'bg-red-500' : ($task['level'] == 'Sedang' ? 'bg-blue-500' : 'bg-green-500') }}">{{ $task['level'] }}</span>
                    </div>
                    @if(!empty($task['urgency_label']))
                    <span class="text-[8px] px-2 py-0.5 rounded-full font-bold {{ $task['urgency'] === 'overdue' ? 'bg-red-100 text-red-600' : ($task['urgency'] === 'urgent' ? 'bg-orange-100 text-orange-600' : 'bg-amber-100 text-amber-600') }}">
                        <i class="fas fa-clock mr-0.5"></i>{{ $task['urgency_label'] }}
                    </span>
                    @endif
                </div>

                @if(!empty($task['pending_to']))
                <div class="mt-3 flex items-center gap-1.5 rounded-lg bg-purple-50 border border-purple-100 px-2 py-1.5 text-[9px] font-bold text-purple-700">
                    <i class="fas fa-hourglass-half"></i>
                    Menunggu Dosen &rarr; {{ $task['pending_to'] }}
                </div>
                @endif

                @if($editable)
                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                    @if(empty($task['pending_to']))
                    <button type="button"
                            @click="openMove({{ $task['id'] }}, @js($task['name']), '{{ $col['key'] }}')"
                            class="text-[9px] bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-bold hover:bg-gray-200 transition">
                        <i class="fas fa-arrow-right-arrow-left mr-1"></i>Pindahkan
                    </button>
                    @else
                    <span></span>
                    @endif
                    <form method="POST" action="{{ route('penyusunan.hapus-tugas', $id) }}"
                          onsubmit="return confirm('Anda yakin ingin menghapus tugas ini?')">
                        @csrf
                        <input type="hidden" name="task_id" value="{{ $task['id'] }}">
                        <button type="submit" class="text-[9px] text-red-400 hover:text-red-600 font-bold">
                            <i class="fas fa-trash mr-1"></i>Hapus
                        </button>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        @if($editable)
        <div class="mt-4" x-data="{ adding: false }">
            <button x-show="!adding" @click="adding = true" class="text-blue-500 text-[11px] font-bold hover:underline">+ Tambah tugas</button>
            <form x-show="adding" x-cloak method="POST" action="{{ route('pelaksanaan.tugas.cepat', $id) }}">
                @csrf
                <input type="hidden" name="column_key" value="{{ $col['key'] }}">
                <input type="text" name="judul_tugas" required
                       x-init="$nextTick(() => {})"
                       @keydown.escape="adding = false"
                       placeholder="Ketik tugas & enter"
                       class="w-full bg-white border border-blue-300 rounded-xl px-4 py-2 text-xs outline-none shadow-inner">
            </form>
        </div>
        @endif
    </div>
    @endforeach

    @if($editable)
    <button type="button" @click="addCol = { label: '', color: 'blue-600', description: '', is_done: false, requires_approval: false, checklist: [] }; colAddModal = true"
            class="w-72 shrink-0 h-[600px] rounded-[2rem] border-2 border-dashed border-gray-300 text-gray-400 hover:border-blue-400 hover:text-blue-500 transition flex flex-col items-center justify-center gap-2">
        <i class="fas fa-plus text-2xl"></i>
        <span class="text-sm font-bold">Tambah Kolom</span>
    </button>
    @endif
</div>
