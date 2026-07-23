@php
    /*
        Papan kanban bersama (mahasiswa & dosen). Kolomnya adalah project_task_columns,
        tugasnya dikelompokkan berdasarkan tasks.status.

        Parameter:
        - $kanban           : keluaran ProjectTaskService::kanbanForProject()
        - $id               : id proyek
        - $lecturer         : mode dosen — buka bukti pengumpulan & tandai sudah direview
        - $canMove          : tombol "Pindahkan" (butuh openMove() di scope Alpine)
        - $canSubmit        : tombol "Kumpulkan" (butuh openSubmit() di scope Alpine)
        Tugas tidak bisa ditambah, diubah, atau dihapus di sini — semuanya lahir di
        Penyusunan.
    */
    $lecturer = $lecturer ?? false;
    $canMove = $canMove ?? false;
    $canSubmit = $canSubmit ?? false;
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

                @if(!empty($task['description']))
                <p class="text-[10px] text-gray-500 leading-snug mb-3 line-clamp-3">{{ $task['description'] }}</p>
                @endif

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-[8px] flex items-center justify-center font-bold text-blue-600 border border-white" title="{{ $task['assignee'] }}">{{ $task['creator'] }}</div>
                        @php
                            $priorityTone = match ($task['level'] ?? '') {
                                'Sulit', 'High' => 'bg-red-500 text-white',
                                'Sedang', 'Medium' => 'bg-yellow-400 text-yellow-950',
                                default => 'bg-blue-500 text-white',
                            };
                        @endphp
                        <span class="text-[8px] px-2 py-0.5 rounded-full font-bold {{ $priorityTone }}">{{ $task['level'] }}</span>
                    </div>
                    @if(!empty($task['urgency_label']))
                    @php
                        $urgencyClass = match ($task['urgency']) {
                            'overdue' => 'bg-red-100 text-red-600',
                            'today' => 'bg-rose-600 text-white',
                            'urgent' => 'bg-orange-100 text-orange-600',
                            default => 'bg-amber-100 text-amber-600',
                        };
                    @endphp
                    <span class="text-[8px] px-2 py-0.5 rounded-full font-bold {{ $urgencyClass }}">
                        <i class="fas fa-clock mr-0.5"></i>{{ $task['urgency_label'] }}
                    </span>
                    @endif
                </div>

                {{-- Bukti pengumpulan mahasiswa: berkas unggahan atau tautan --}}
                @if(!empty($task['submission']))
                <div class="mt-3 space-y-1.5">
                    <a href="{{ $task['submission']['url'] }}" target="_blank" rel="noopener"
                       class="flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-2 py-1.5 text-[9px] font-bold text-blue-700 hover:bg-blue-100 transition">
                        <i class="fas {{ $task['submission']['kind'] === 'link' ? 'fa-link' : ($task['submission']['is_image'] ? 'fa-image' : 'fa-file-arrow-down') }} shrink-0"></i>
                        <span class="truncate">{{ $task['submission']['kind'] === 'link' ? 'Buka link tugas' : $task['submission']['label'] }}</span>
                    </a>

                    @if(!empty($task['reviewed_at']))
                    <div class="flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-emerald-50 px-2 py-1.5 text-[9px] font-bold text-emerald-700">
                        <i class="fas fa-circle-check"></i>
                        Sudah direview &middot; {{ $task['reviewed_at'] }}
                    </div>
                    @elseif($lecturer)
                    <form method="POST" action="{{ route('dosen.pelaksanaan.tandai-review', [$id, $task['id']]) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-lg border border-emerald-200 bg-white px-2 py-1.5 text-[9px] font-bold text-emerald-700 hover:bg-emerald-50 transition">
                            <i class="fas fa-check mr-1"></i>Tandai Sudah Direview
                        </button>
                    </form>
                    @endif
                </div>
                @elseif($lecturer)
                <p class="mt-3 rounded-lg border border-dashed border-gray-200 px-2 py-1.5 text-[9px] font-bold text-gray-400">
                    <i class="fas fa-inbox mr-1"></i>Belum ada bukti pengumpulan
                </p>
                @endif

                @if(!empty($task['pending_to']))
                <div class="mt-3 flex items-center gap-1.5 rounded-lg bg-purple-50 border border-purple-100 px-2 py-1.5 text-[9px] font-bold text-purple-700">
                    <i class="fas fa-hourglass-half"></i>
                    Menunggu Dosen &rarr; {{ $task['pending_to'] }}
                </div>
                @endif

                @if($canMove || $canSubmit)
                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center gap-2">
                    @if($canMove && empty($task['pending_to']))
                    <button type="button"
                            @click="openMove({{ $task['id'] }}, @js($task['name']), '{{ $col['key'] }}')"
                            class="text-[9px] bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-bold hover:bg-gray-200 transition">
                        <i class="fas fa-arrow-right-arrow-left mr-1"></i>Pindahkan
                    </button>
                    @endif

                    @if($canSubmit)
                    <button type="button"
                            @click="openSubmit({{ $task['id'] }}, @js($task['name']), @js($task['submission'] ?? null))"
                            class="ml-auto text-[9px] bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-bold hover:bg-blue-100 transition">
                        <i class="fas fa-paper-plane mr-1"></i>{{ empty($task['submission']) ? 'Kumpulkan' : 'Ganti Hasil' }}
                    </button>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

</div>
