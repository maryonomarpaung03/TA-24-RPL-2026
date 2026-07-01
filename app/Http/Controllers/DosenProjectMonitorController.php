<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectTaskService;
use App\Support\ProjectAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenProjectMonitorController extends Controller
{
    /** @var list<string> */
    private const APPROVED_STATUSES = ['active', 'completed'];

    public function __construct(
        private readonly ProjectTaskService $tasks
    ) {}

    public function planning(int $id)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        return view('DosenPenyusunan', [
            'project' => $project,
            'namaProjek' => $project->title,
            'id' => $project->id,
            'tasks' => $this->planningRows((int) $project->id),
        ]);
    }

    public function execution(int $id)
    {
        $project = $this->authorizeLecturerProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        return view('DosenPelaksanaan', [
            'project' => $project,
            'namaProjek' => $project->title,
            'id' => $project->id,
            'columns' => $this->tasks->columnsForProject((int) $project->id),
            'kanban' => $this->tasks->kanbanForProject((int) $project->id),
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

        return $taskData
            ->map(fn ($task, $index) => [
                'id' => $task->id,
                'no' => $index + 1,
                'judul' => $task->task_title,
                'deskripsi' => $task->description ?? '-',
                'mulai' => $task->start_date ? Carbon::parse($task->start_date)->format('Y-m-d') : '-',
                'selesai' => $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : '-',
                'pj' => $task->full_name ?? 'Belum Ditentukan',
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

        if (! in_array($project->status, self::APPROVED_STATUSES, true)) {
            return redirect()
                ->route('dosen.proyek-mahasiswa')
                ->with('error', 'Proyek ini belum disetujui atau masih menunggu persetujuan.');
        }

        return $project;
    }
}
