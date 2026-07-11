@extends('layouts.app')

@section('title', 'Pelaksanaan & Evaluasi - DELPRO')
@section('root_data', '{ 
                        sidebarOpen: true, 
                        commentModal: false, 
                        editModal: false, 
                        addComment: false,
                        activeColumn:null, 
                        selectedTask:{
                            id:null,
                            title:"",
                            description:"",
                            priority:"medium",
                            status:"pending",
                            progress:0, due:"",
                            link:"",
                            submission_type:"link",
                            attachment_name:"",
                            attachment_url:""},
                        }')

@section('content')

<div x-data="taskManager()"class="w-full space-y-6">

    {{-- ========================================================= --}}
    {{-- HEADER PROJECT --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $namaProjek }}</h2>
            <p class="text-[11px] uppercase tracking-wider text-gray-400 mt-2">Projek Saya /
                <span class="text-blue-600">Pelaksanaan & Evaluasi</span>
            </p>
        </div>
        <button
            class="text-2xl text-gray-400 hover:text-blue-600 transition">
            <i class="fas fa-cog"></i>
        </button>
    </div>
    @include('partials.filter-bar', [
        'action' => route('pelaksanaan', $id),
        'search' => [
            'name' => 'q',
            'value' => $filterState['q'],
            'placeholder' => 'Cari judul atau deskripsi tugas',
        ],
        'filters' => [
            ['name' => 'pj', 'label' => 'Penanggung Jawab', 'value' => $filterState['pj'], 'options' => $pjOptions],
            ['name' => 'prioritas', 'label' => 'Prioritas', 'value' => $filterState['prioritas'], 'options' => $prioritasOptions],
            ['name' => 'tenggat', 'label' => 'Tenggat', 'value' => $filterState['tenggat'], 'options' => $tenggatOptions],
        ],
        'summary' => 'Menampilkan '.$shownTasks.' dari '.$totalTasks.' tugas di papan.',
        'extraButton' => [
            'click' => 'boardModal = true',
            'label' => '+ Dashboard',
        ],
    ])

    {{-- Modal: tambah kolom papan --}}
    <div x-show="boardModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(15,23,42,0.45);"
         @keydown.escape.window="boardModal = false">
        <div @click.outside="boardModal = false"
             class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900">Tambah Dashboard</h3>
            <p class="text-sm text-slate-400 mt-1">Buat kolom baru pada papan pelaksanaan.</p>

            <form action="{{ route('boards.store', $id) }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <input type="text" name="name" required maxlength="100"
                       placeholder="Nama dashboard, mis. Review"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:bg-white transition">

                <div class="flex justify-end gap-2">
                    <button type="button" @click="boardModal = false"
                            class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- KANBAN BOARD --}}
    {{-- ========================================================= --}}

    <div class="flex gap-6 overflow-x-auto pb-4 w-full">

@foreach($boards as $board)

@php
$totalTask = $board->tasks->count();
$completedTask = $board->tasks->where('status','completed')->count();
$progress = $totalTask ? round(($completedTask/$totalTask)*100) : 0;
@endphp

<div class="flex-1 basis-0 min-w-[280px] bg-white border border-gray-100 rounded-3xl shadow-md p-5 flex flex-col">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <h3 class="font-bold">{{ $board->name }}</h3>
        </div>

        <span class="text-xs text-gray-500">
            {{ $totalTask }} Task
        </span>
    </div>

    {{-- Progress --}}
    <div class="mb-5">
        <div class="flex justify-between text-xs mb-1">
            <span>{{ $completedTask }}/{{ $totalTask }}</span>
            <span>{{ $progress }}%</span>
        </div>

        <div class="w-full h-2 rounded-full bg-gray-200">
            <div
                class="h-2 rounded-full bg-blue-500"
                style="width: {{ $progress }}%">
            </div>
        </div>
    </div>

    {{-- TASK LIST --}}
    <div
        class="task-list flex-1 space-y-3"
        data-board-id="{{ $board->id }}"
    >

        @forelse($board->tasks as $task)

        <div
            data-task-id="{{ $task->id }}"
            class="task-card bg-white rounded-2xl shadow-sm p-4 border border-gray-200 hover:shadow-md transition">

            <div class="flex justify-between">

                <h4 class="font-semibold">
                    {{ $task->task_title }}
                </h4>

                <button
    @click="
        editModal = true;
        selectedTask = {
            id: {{ $task->id }},
            title: @js($task->task_title),
            description: @js($task->description),
            link: @js($task->link),
            submission_type: @js($task->submission_type ?: ($task->attachment_path ? 'file' : 'link')),
            attachment_name: @js($task->attachment_name),
            attachment_url: @js($task->attachment_path ? asset('storage/'.$task->attachment_path) : null),
            priority: '{{ $task->priority }}',
            status: '{{ $task->status }}',
            progress: {{ $task->progress_percent }},
            due: '{{ $task->due_date }}',
            board_id: {{ $task->board_id }}
        }
    "
    class="text-gray-400 hover:text-blue-600"
>
    <i class="fas fa-edit"></i>
</button>
<button
    @click="
        addComment=true;
        selectedTask.id={{ $task->id }};
        selectedTask.title=@js($task->task_title);
        selectedTask.comment='';
    "
    class="text-blue-600 hover:text-blue-800">

    <i class="fas fa-comment"></i>

</button>
            </div>

            @if($task->description)
                <p class="text-xs text-gray-500 mt-2">
                    {{ $task->description }}
                </p>
            @endif

            {{-- HASIL SUBMIT: link atau berkas --}}
            @if($task->attachment_path)
                <a href="{{ asset('storage/'.$task->attachment_path) }}" target="_blank"
                   class="mt-2 inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-100">
                    <i class="fas {{ str_starts_with((string) $task->attachment_mime, 'image/') ? 'fa-image' : 'fa-file-alt' }}"></i>
                    <span class="truncate">{{ $task->attachment_name }}</span>
                </a>
            @elseif($task->link)
                <a href="{{ $task->link }}" target="_blank"
                   class="mt-2 inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-100">
                    <i class="fas fa-link"></i>
                    <span class="truncate">Link tugas</span>
                </a>
            @endif

            {{-- COMMENT --}}
@if($task->comments->count())

<div class="mt-3 space-y-2">

    @foreach($task->comments as $comment)

    <div class="flex gap-2">

        <div class="w-7 h-7 rounded-full bg-blue-500 text-white flex items-center justify-center text-[10px]">
            C
        </div>

        <div class="flex-1 bg-gray-100 rounded-xl px-3 py-2">

            <p class="text-xs">
                {{ $comment->comment }}
            </p>

            <small class="text-gray-400">
                {{ $comment->created_at->diffForHumans() }}
            </small>

        </div>

    </div>

    @endforeach

</div>

@endif
            <div class="flex justify-between mt-4">

                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">
                    {{ strtoupper($task->priority) }}
                </span>

                <span class="text-xs">
                    {{ $task->progress_percent }}%
                </span>

            </div>

        </div>

        @empty

        <div class="rounded-xl border-2 border-dashed border-gray-300 p-6 text-center text-gray-400 text-sm">
            Belum ada task
        </div>

        @endforelse

    </div>
    {{-- ADD TASK --}}
    <div class="mt-5" x-data="{adding:false}">

        <button
            x-show="!adding"
            @click="adding=true"
            class="w-full rounded-xl border-2 border-dashed border-blue-300 py-2 text-blue-600 hover:bg-blue-50">

            + Tambah Task

        </button>

        <form
            x-show="adding"
            action="{{ route('tasks.store',$board->id) }}"
            method="POST"
            class="mt-2">

            @csrf

            <input
                type="text"
                name="title"
                class="w-full rounded-xl border p-2"
                placeholder="Nama Task">
            
        <div class="flex justify-end gap-2">

            {{-- Cancel --}}
            <button
                type="button"
                @click="adding=false"
                class="px-4 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300">

                Batal

            </button>

            {{-- Submit --}}
            <button
                type="submit"
                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">

                Simpan Task

            </button>

        </div>
        </form>

    </div>

</div>

@endforeach
<div class="w-[340px] min-w-[340px] bg-green-50 rounded-3xl shadow-md p-5 flex flex-col">

    <div class="flex justify-between items-center mb-4">

        <div class="flex items-center gap-2">

            <div class="w-3 h-3 rounded-full bg-green-600"></div>

            <h3 class="font-bold text-green-700">
                Complete
            </h3>

        </div>

    </div>

    <div class="space-y-3">

        @foreach($boards as $board)

            @foreach($board->tasks->where('status','completed') as $task)

                <div class="bg-white rounded-2xl shadow p-4 border border-green-200">

                    <div class="flex justify-between">

                        <h4 class="font-semibold">
                            {{ $task->task_title }}
                        </h4>

                        <span class="text-green-600">
                            <i class="fas fa-check-circle"></i>
                        </span>

                    </div>

                    @if($task->description)

                        <p class="text-xs text-gray-500 mt-2">
                            {{ $task->description }}
                        </p>

                    @endif

                    <div class="flex justify-between mt-3">

                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">
                            COMPLETE
                        </span>

                        <span class="text-xs">
                            {{ $board->name }}
                        </span>

                    </div>

                </div>

            @endforeach

        @endforeach

    </div>

</div>
</div>
    {{-- ========================================================= --}}
    {{-- Modal Edit --}}
    {{-- ========================================================= --}}
<div x-show="editModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

    <div
        @click.outside="editModal=false"
        class="bg-white rounded-[2rem] shadow-2xl w-full max-w-xl p-8">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-8">

            <h2 class="text-2xl font-bold text-gray-800">
                Edit Task
            </h2>

            <button
                type="button"
                @click="editModal=false"
                class="text-gray-400 hover:text-red-500">

                <i class="fas fa-times text-xl"></i>

            </button>

        </div>

        {{-- FORM --}}
        <form
            method="POST"
            enctype="multipart/form-data"
            :action="'{{ url('/tasks') }}/' + selectedTask.id + '/update'"
            class="space-y-5">

            @csrf

            {{-- Nama Task --}}
            <div>

                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                    Nama Task
                </label>

                <input
                    type="text"
                    name="task_title"
                    x-model="selectedTask.title"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none focus:border-blue-500">

            </div>

            {{-- Deskripsi --}}
            <div>

                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                    Deskripsi
                </label>

                <textarea
                    rows="4"
                    name="description"
                    x-model="selectedTask.description"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none resize-none focus:border-blue-500"></textarea>

            </div>

            <div class="grid grid-cols-2 gap-4">
                  <select
        name="board_id"
        x-model="selectedTask.board_id"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">

        @foreach($allBoards as $board)

            <option value="{{ $board->id }}">
                {{ $board->name }}
            </option>

        @endforeach

    </select>
                {{-- Priority --}}
                <div>

                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Priority
                    </label>

                    <select
                        name="priority"
                        x-model="selectedTask.priority"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none">

                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>

                    </select>

                </div>

                {{-- Status --}}
             {{-- Status --}}
    <div>

        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
            Status
        </label>

        <select
            name="status"
            x-model="selectedTask.status"
            @change="
                if(selectedTask.status == 'completed'){
                    selectedTask.progress = 100;
                }
            "
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">

            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>

        </select>

    </div>

    {{-- Progress --}}
    <div>

        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
            Progress (%)
        </label>

        <input
            type="number"
            name="progress_percent"
            min="0"
            max="100"
            x-model="selectedTask.progress"
            :readonly="selectedTask.status == 'completed'"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none">

    </div>

                {{-- Submit Tugas: link atau berkas (foto/dokumen) --}}
                <div class="col-span-2 rounded-2xl border border-gray-200 bg-gray-50/60 p-4 space-y-4">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Submit Tugas
                        </label>

                        <select
                            name="submission_type"
                            x-model="selectedTask.submission_type"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none focus:border-blue-500">

                            <option value="link">Link</option>
                            <option value="file">File (foto / dokumen)</option>

                        </select>
                    </div>

                    {{-- Pilihan: Link --}}
                    <div x-show="selectedTask.submission_type === 'link'" x-cloak>

                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Link Tugas
                        </label>

                        <input
                            type="url"
                            name="link"
                            x-model="selectedTask.link"
                            :disabled="selectedTask.submission_type !== 'link'"
                            placeholder="https://..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none focus:border-blue-500">

                        <p class="mt-1 text-[11px] text-gray-400">
                            Contoh: tautan Google Drive, GitHub, atau Figma.
                        </p>

                    </div>

                    {{-- Pilihan: File --}}
                    <div x-show="selectedTask.submission_type === 'file'" x-cloak>

                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Unggah Berkas
                        </label>

                        <input
                            type="file"
                            name="attachment"
                            :disabled="selectedTask.submission_type !== 'file'"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white file:font-semibold hover:file:bg-blue-700">

                        <p class="mt-1 text-[11px] text-gray-400">
                            Foto (JPG/PNG/WEBP/GIF) atau dokumen (PDF/DOC/XLS/PPT/TXT/ZIP), maks. 10 MB.
                        </p>

                        <template x-if="selectedTask.attachment_url">
                            <p class="mt-2 text-xs text-gray-500">
                                Berkas saat ini:
                                <a :href="selectedTask.attachment_url" target="_blank"
                                   class="font-bold text-blue-600 hover:underline"
                                   x-text="selectedTask.attachment_name"></a>
                                <span class="text-gray-400">— unggah berkas baru untuk menggantinya.</span>
                            </p>
                        </template>

                    </div>

                </div>

                {{-- Deadline --}}
                <div>

                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                        Deadline
                    </label>

                    <input
                        type="date"
                        name="due_date"
                        x-model="selectedTask.due"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none">

                </div>

            </div>
            

            {{-- BUTTON --}}
            <div class="flex justify-end gap-3 pt-4">

                <button
                    type="button"
                    @click="editModal=false"
                    class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">

                    Batal

                </button>

                <button
                    type="submit"
                    class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">

                    Simpan

                </button>

            </div>

        </form>

    </div>

</div>
{{-- ========================================================= --}}
{{-- Modal Add Comment --}}
{{-- ========================================================= --}}

<div x-show="addComment"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

    <div
        @click.outside="addComment=false"
        class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">

            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    Tambah Komentar
                </h2>

                <p class="text-sm text-gray-500 mt-1"
                   x-text="selectedTask.title">
                </p>
            </div>

            <button
                @click="addComment=false"
                class="text-gray-400 hover:text-red-500">

                <i class="fas fa-times text-xl"></i>

            </button>

        </div>

        {{-- FORM --}}
        <form
    method="POST"
    :action="'{{ url('/tasks') }}/' + selectedTask.id + '/comment'">

    @csrf

    <div>

        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
            Komentar
        </label>

        <textarea
            rows="6"
            name="comment"
            x-model="selectedTask.comment"
            placeholder="Masukkan komentar..."
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 outline-none resize-none focus:border-blue-500">
        </textarea>

    </div>

    <div class="flex justify-end gap-3 mt-6">

        <button
            type="button"
            @click="addComment=false"
            class="px-6 py-3 rounded-xl bg-gray-200 text-gray-600 font-semibold hover:bg-gray-300">

            Batal

        </button>

        <button
            type="submit"
            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">

            Simpan

        </button>

    </div>

</form>

    </div>

</div>

{{-- ===================================================== --}}
{{-- MODAL KOMENTAR --}}
{{-- ===================================================== --}}

<div x-show="commentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div @click.outside="commentModal=false" class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Komentar</h2>
            <button @click="commentModal=false"class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <textarea rows="6" placeholder="Tulis komentar..." class="w-full rounded-xl border border-gray-200 bg-gray-50 p-4 outline-none resize-none focus:border-blue-500"></textarea>
        <div class="flex justify-end mt-5">
            <button class="px-6 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700">Kirim Komentar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>

function taskManager(){

    return{

        boardModal: false,

        saveTask(){

            fetch("/tasks/" + this.selectedTask.id + "/update",{

                method:"POST",

                headers:{
                    "Content-Type":"application/json",
                    "Accept":"application/json",
                    "X-CSRF-TOKEN":"{{ csrf_token() }}"
                },

                body:JSON.stringify({

                    task_title:this.selectedTask.title,
                    description:this.selectedTask.description,
                    priority:this.selectedTask.priority,
                    status:this.selectedTask.status,
                    progress_percent:this.selectedTask.progress,
                    due_date:this.selectedTask.due,
                    board_id:this.selectedTask.board_id

                })

            })

            .then(r=>r.json())

            .then(data=>{

                if(data.success){

                    this.editModal=false;

                    location.reload();

                }

            });

        },

        deleteTask(id){

            if(!confirm("Hapus task?")) return;

            fetch("/tasks/"+id,{

                method:"DELETE",

                headers:{
                    "Accept":"application/json",
                    "X-CSRF-TOKEN":"{{ csrf_token() }}"
                }

            })

            .then(r=>r.json())

            .then(data=>{

                if(data.success){

                    location.reload();

                }

            });

        }

    }

}

document.addEventListener("DOMContentLoaded",function(){

    document.querySelectorAll(".task-list").forEach(function(board){

        new Sortable(board,{

            group:"kanban",

            animation:180,

            ghostClass:"opacity-50",

            draggable:".task-card",

            onEnd:function(evt){

                let taskId=evt.item.dataset.taskId;

                let boardId=evt.to.dataset.boardId;

                fetch("{{ route('tasks.move') }}",{

                    method:"POST",

                    headers:{

                        "Content-Type":"application/json",

                        "Accept":"application/json",

                        "X-CSRF-TOKEN":"{{ csrf_token() }}"

                    },

                    body:JSON.stringify({

                        task_id:taskId,

                        board_id:boardId

                    })

                })

                .then(r=>r.json())

                .then(data=>{

                    if(data.success){

                        location.reload();

                    }

                });

            }

        });

    });

});

</script>
@endpush