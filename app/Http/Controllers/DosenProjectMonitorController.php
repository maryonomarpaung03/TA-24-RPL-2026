<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectTaskService;
use App\Support\ProjectAccess;
use App\Support\TaskFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenProjectMonitorController extends Controller
{
    /** Label prioritas seperti yang dipakai ProjectTaskService::priorityLabel(). */
    private const PRIORITY_OPTIONS = [
        'Sulit' => 'Sulit',
        'Sedang' => 'Sedang',
        'Mudah' => 'Mudah',
    ];

    /** Rentang tenggat di papan kanban dihitung dari sisa hari. */
    private const KANBAN_DEADLINE_OPTIONS = [
        'terlewat' => 'Sudah lewat tenggat',
        'minggu_ini' => 'Jatuh tempo ≤ 7 hari',
        'bulan_ini' => 'Jatuh tempo ≤ 30 hari',
        'tanpa_tenggat' => 'Tanpa tenggat',
    ];

    /**
     * Saring tugas di papan kanban, kolomnya tetap utuh agar papan tidak berubah bentuk.
     *
     * @param  list<array<string, mixed>>  $kanban
     * @param  array<string, string>  $filters
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int}
     */
    private function filterKanban(array $kanban, array $filters): array
    {
        $total = 0;
        $shown = 0;

        foreach ($kanban as $i => $column) {
            $tasks = $column['tasks'] ?? [];
            $total += count($tasks);
            $isDoneColumn = (bool) ($column['is_done'] ?? false);

            $kept = array_values(array_filter($tasks, function (array $task) use ($filters, $isDoneColumn) {
                if ($filters['pj'] !== '' && (string) ($task['assigned_to'] ?? '') !== $filters['pj']) {
                    return false;
                }

                if ($filters['prioritas'] !== '' && ($task['level'] ?? '') !== $filters['prioritas']) {
                    return false;
                }

                if ($filters['q'] !== '') {
                    $haystack = mb_strtolower(($task['name'] ?? '').' '.($task['description'] ?? ''));

                    if (! str_contains($haystack, mb_strtolower($filters['q']))) {
                        return false;
                    }
                }

                // Tugas yang sudah di kolom Selesai tidak dihitung terlewat.
                if ($filters['tenggat'] === 'terlewat'
                    && ($isDoneColumn || ($task['days_left'] ?? null) === null || $task['days_left'] >= 0)) {
                    return false;
                }

                if ($filters['tenggat'] === 'tanpa_tenggat' && ($task['due_date'] ?? null) !== null) {
                    return false;
                }

                if ($filters['tenggat'] === 'minggu_ini' && ! (($task['days_left'] ?? null) !== null && $task['days_left'] >= 0 && $task['days_left'] <= 7)) {
                    return false;
                }

                if ($filters['tenggat'] === 'bulan_ini' && ! (($task['days_left'] ?? null) !== null && $task['days_left'] >= 0 && $task['days_left'] <= 30)) {
                    return false;
                }

                return true;
            }));

            $shown += count($kept);
            $kanban[$i]['tasks'] = $kept;
        }

        return [$kanban, $shown, $total];
    }

    /**
     * @param  list<array<string, mixed>>  $kanban
     * @return list<array<string, mixed>>
     */
    private function flattenKanban(array $kanban): array
    {
        $tasks = [];

        foreach ($kanban as $column) {
            foreach ($column['tasks'] ?? [] as $task) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    public function __construct(
        private readonly ProjectTaskService $tasks
    ) {}

    public function planning(Request $request, int $id)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        $allTasks = $this->planningRows((int) $project->id);

        $filters = [
            'status' => (string) $request->query('status', ''),
            'pj' => (string) $request->query('pj', ''),
            'tenggat' => (string) $request->query('tenggat', ''),
        ];

        return view('DosenPenyusunan', [
            'project' => $project,
            'namaProjek' => $project->title,
            'id' => $project->id,
            'tasks' => TaskFilter::apply($allTasks, $filters),
            'totalTasks' => count($allTasks),
            'filterState' => $filters,
            'statusOptions' => TaskFilter::STATUS_OPTIONS,
            'tenggatOptions' => TaskFilter::DEADLINE_OPTIONS,
            'pjOptions' => TaskFilter::assigneeOptions($allTasks),
        ]);
    }

    public function execution(Request $request, int $id)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        $kanban = $this->tasks->kanbanForProject((int) $project->id);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'pj' => (string) $request->query('pj', ''),
            'prioritas' => (string) $request->query('prioritas', ''),
            'tenggat' => (string) $request->query('tenggat', ''),
        ];

        [$kanbanFiltered, $shown, $total] = $this->filterKanban($kanban, $filters);

        return view('DosenPelaksanaan', [
            'project' => $project,
            'namaProjek' => $project->title,
            'shownTasks' => $shown,
            'totalTasks' => $total,
            'filterState' => $filters,
            'prioritasOptions' => self::PRIORITY_OPTIONS,
            'tenggatOptions' => self::KANBAN_DEADLINE_OPTIONS,
            'pjOptions' => TaskFilter::assigneeOptions($this->flattenKanban($kanban)),
            'id' => $project->id,
            'columns' => $this->tasks->columnsForProject((int) $project->id),
            'kanban' => $kanbanFiltered,
            'contribution' => $this->tasks->contributionForProject((int) $project->id),
            'progress' => $this->tasks->progressForProject((int) $project->id),
            'pendingApprovals' => $this->tasks->pendingApprovalsForProject((int) $project->id),
        ]);
    }

    public function approve(int $id, int $approvalId)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->approveTask((int) $project->id, $approvalId, Auth::user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Tugas disetujui dan dipindahkan.');
    }

    /** Tandai sebuah tugas sudah ditinjau dosen (bukan approve/tolak). */
    public function markReviewed(int $id, int $taskId)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->markReviewed((int) $project->id, $taskId, (int) Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Tugas ditandai sudah direview. Mahasiswa akan mendapat notifikasi.');
    }

    public function reject(\Illuminate\Http\Request $request, int $id, int $approvalId)
    {
        $request->validate(['note' => 'nullable|string|max:1000']);

        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->rejectTask((int) $project->id, $approvalId, Auth::user(), $request->input('note'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Tugas ditolak. Catatan dikirim ke mahasiswa.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function planningRows(int $projectId): array
    {
        $taskData = DB::table('tasks')
            ->leftJoin('users', 'tasks.assigned_to', '=', 'users.id')
            ->where('tasks.project_id', $projectId)
            ->orderBy('tasks.created_at')
            ->select('tasks.*', 'users.full_name')
            ->get();

        $commentsByTask = $this->commentsByTask($projectId, $taskData->pluck('id'));

        $doneKeys = $this->tasks->doneKeysForProject($projectId);

        return $taskData
            ->map(fn ($task, $index) => [
                'id' => $task->id,
                'no' => $index + 1,
                'judul' => $task->task_title,
                'deskripsi' => $task->description ?? '-',
                'mulai' => $task->start_date ? Carbon::parse($task->start_date)->format('Y-m-d') : '-',
                'selesai' => $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : '-',
                'pj' => $task->full_name ?? 'Belum Ditentukan',
                'assigned_to' => (int) $task->assigned_to,
                'status' => ProjectTaskService::taskStatusMeta($task->status, $task->due_date, $doneKeys),
                'comments' => $commentsByTask[$task->id] ?? [],
            ])
            ->all();
    }

    /**
     * Komentar tiap tugas, dikelompokkan per task_id.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $taskIds
     * @return array<int, list<array{from: string, text: string, time: string}>>
     */
    private function commentsByTask(int $projectId, $taskIds): array
    {
        if ($taskIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('discussions')
            ->leftJoin('users', 'users.id', '=', 'discussions.user_id')
            ->where('discussions.project_id', $projectId)
            ->whereIn('discussions.task_id', $taskIds)
            ->orderBy('discussions.created_at')
            ->select('discussions.task_id', 'discussions.message', 'discussions.created_at', 'users.full_name', 'users.name', 'users.role')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $name = $row->full_name ?: $row->name ?: 'Anggota';
            $map[(int) $row->task_id][] = [
                'from' => $name.($row->role === 'lecturer' ? ' (Dosen)' : ''),
                'text' => $row->message,
                'time' => Carbon::parse($row->created_at)->format('d M Y H:i'),
            ];
        }

        return $map;
    }

    /**
     * @return Project|\Illuminate\Http\RedirectResponse
     */
    private function authorizeLecturerProject(int $id)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        if (! in_array($project->status, ProjectAccess::lecturerVisibleStatuses(), true)) {
            return redirect()
                ->route('dosen.proyek-mahasiswa')
                ->with('error', 'Proyek ini belum disetujui atau masih menunggu persetujuan.');
        }

        return $project;
    }
}
