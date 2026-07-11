<?php

namespace App\Http\Controllers;

use App\Models\ProjectBoard;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\ProjectTaskService;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use App\Support\TaskFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PelaksanaanController extends Controller
{
    public function index(Request $request, $id)
    {
        $selected = ProjectCatalog::find($id);

        if (!$selected) {

            return redirect()
                ->route('my-project')
                ->with('error', 'Project tidak ditemukan.');

        }

        $boards = ProjectBoard::with(['tasks.comments'])
            ->where('project_id', $id)
            ->orderBy('position')
            ->get();

        $members = app(ProjectTaskService::class)->assignableMembers((int) $id);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'pj' => (string) $request->query('pj', ''),
            'prioritas' => (string) $request->query('prioritas', ''),
            'tenggat' => (string) $request->query('tenggat', ''),
        ];

        $totalTasks = $boards->sum(fn (ProjectBoard $board) => $board->tasks->count());

        // Papan tetap utuh; hanya isi tugas tiap kolom yang disaring.
        $boards->each(function (ProjectBoard $board) use ($filters) {
            $board->setRelation('tasks', $board->tasks->filter(
                fn (Task $task) => $this->taskMatchesFilters($task, $filters)
            )->values());
        });

        $shownTasks = $boards->sum(fn (ProjectBoard $board) => $board->tasks->count());

        return view('Pelaksanaan', [

            'user' => PjblContext::viewer(),

            'namaProjek' => $selected['name'],

            'id' => $id,

            'boards' => $boards,

            'allBoards' => $boards,

            'filterState' => $filters,
            'totalTasks' => $totalTasks,
            'shownTasks' => $shownTasks,
            'pjOptions' => collect($members)->mapWithKeys(fn ($m) => [$m->id => $m->full_name])->all(),
            'prioritasOptions' => [
                'high' => 'Tinggi',
                'medium' => 'Sedang',
                'low' => 'Rendah',
            ],
            'tenggatOptions' => TaskFilter::DEADLINE_OPTIONS,

        ]);
    }

    /** @param array<string, string> $filters */
    private function taskMatchesFilters(Task $task, array $filters): bool
    {
        if ($filters['pj'] !== '' && (string) $task->assigned_to !== $filters['pj']) {
            return false;
        }

        if ($filters['prioritas'] !== '' && $task->priority !== $filters['prioritas']) {
            return false;
        }

        if ($filters['q'] !== '') {
            $haystack = mb_strtolower($task->task_title.' '.$task->description);

            if (! str_contains($haystack, mb_strtolower($filters['q']))) {
                return false;
            }
        }

        if ($filters['tenggat'] !== '') {
            $due = $task->due_date ? Carbon::parse($task->due_date) : null;

            return match ($filters['tenggat']) {
                'tanpa_tenggat' => $due === null,
                'terlewat' => $due !== null && $due->endOfDay()->isPast() && $task->status !== 'completed',
                'minggu_ini' => $due !== null && $due->between(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()),
                'bulan_ini' => $due !== null && $due->between(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
                default => true,
            };
        }

        return true;
    }
    public function moveTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'board_id' => 'required|exists:project_boards,id',
        ]);

        $task = Task::findOrFail($request->task_id);

        $task->board_id = $request->board_id;
        $task->save();

        return response()->json([
            'success' => true
        ]);
    }
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'name' => 'required|max:100'
        ]);

        ProjectBoard::create([
            'project_id' => $projectId,
            'name' => $request->name,
            'position' => ProjectBoard::where('project_id', $projectId)->count() + 1,
            'is_completed' => false
        ]);


        return redirect()
            ->route('pelaksanaan', $projectId)
            ->with('success', 'Board berhasil ditambahkan.');
    }
    public function comment(Request $request, $taskId)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $task = Task::findOrFail($taskId);

        TaskComment::create([
            'task_id' => $task->id,
            'comment' => $request->comment,
        ]);

        return redirect()
            ->route('pelaksanaan', $task->project_id)
            ->with('success', 'Komentar berhasil ditambahkan.');
    }
    public function updateTask(Request $request, $taskId)
    {
        $request->validate([
            'task_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'submission_type' => 'nullable|in:link,file',
            'link' => 'nullable|url|required_if:submission_type,link',
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip',
            ],
            'board_id' => 'required|exists:project_boards,id',
            'status' => 'required|in:pending,in_progress,completed',
            'progress_percent' => 'required|integer|min:0|max:100',
            'due_date' => 'nullable|date',
        ], [
            'link.required_if' => 'Link tugas wajib diisi bila pengumpulan berupa link.',
            'attachment.mimes' => 'Berkas harus berupa foto (jpg/png/webp/gif) atau dokumen (pdf/doc/xls/ppt/txt/zip).',
            'attachment.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $task = Task::findOrFail($taskId);

        $task->update([
            'board_id' => $request->board_id,
            'task_title' => $request->task_title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'progress_percent' => $request->progress_percent,
            'due_date' => $request->due_date,
        ] + $this->submissionPayload($request, $task));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('pelaksanaan', $task->project_id)
            ->with('success', 'Task berhasil diperbarui.');
    }

    /**
     * Kolom pengumpulan hasil tugas: berupa link, atau berkas (foto/dokumen)
     * yang diunggah. Berkas lama dihapus saat diganti atau saat beralih ke link.
     *
     * @return array<string, mixed>
     */
    private function submissionPayload(Request $request, Task $task): array
    {
        $type = $request->input('submission_type');

        // Permintaan JSON lama tidak mengirim submission_type: hanya sentuh link
        // bila field-nya memang dikirim, supaya pengumpulan tidak ikut terhapus.
        if ($type === null) {
            return $request->has('link') ? ['link' => $request->input('link')] : [];
        }

        if ($type === 'link') {
            $this->deleteAttachment($task);

            return [
                'submission_type' => 'link',
                'link' => $request->input('link'),
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
            ];
        }

        // Tipe file tanpa unggahan baru: pertahankan berkas yang sudah ada.
        if (! $request->hasFile('attachment')) {
            return ['submission_type' => 'file', 'link' => null];
        }

        $this->deleteAttachment($task);

        $file = $request->file('attachment');

        return [
            'submission_type' => 'file',
            'link' => null,
            'attachment_path' => $file->store('task_submissions/'.$task->project_id, 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
        ];
    }

    private function deleteAttachment(Task $task): void
    {
        if ($task->attachment_path) {
            Storage::disk('public')->delete($task->attachment_path);
        }
    }

    public function destroyTask(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $projectId = $task->project_id;

        $this->deleteAttachment($task);
        $task->comments()->delete();
        $task->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('pelaksanaan', $projectId)
            ->with('success', 'Task berhasil dihapus.');
    }
}