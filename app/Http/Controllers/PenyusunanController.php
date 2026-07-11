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

class PenyusunanController extends Controller
{
    public function __construct(
        private readonly ProjectTaskService $tasks
    ) {}

    public function index(Request $request, $id)
    {
        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Projek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $taskData = DB::table('tasks')
            ->leftJoin('users', 'tasks.assigned_to', '=', 'users.id')
            ->where('tasks.project_id', $project->id)
            ->select('tasks.*', 'users.full_name')
            ->orderBy('tasks.created_at')
            ->get();

        // Komentar tiap tugas (dari tabel discussions).
        $commentsByTask = [];
        $taskIds = $taskData->pluck('id');

        if ($taskIds->isNotEmpty()) {
            $commentRows = DB::table('discussions')
                ->leftJoin('users', 'users.id', '=', 'discussions.user_id')
                ->whereIn('discussions.task_id', $taskIds)
                ->orderBy('discussions.created_at')
                ->select(
                    'discussions.task_id',
                    'discussions.message',
                    'discussions.created_at',
                    'users.full_name',
                    'users.name'
                )
                ->get();

            foreach ($commentRows as $row) {
                $commentsByTask[$row->task_id][] = [
                    'from' => $row->full_name ?: $row->name ?: 'Anggota',
                    'text' => $row->message,
                    'time' => Carbon::parse($row->created_at)->format('d M Y H:i'),
                ];
            }
        }

        $doneKeys = $this->tasks->doneKeysForProject((int) $project->id);

        $tasks = $taskData->map(function ($task, $index) use ($commentsByTask, $doneKeys) {
            $urgency = ProjectTaskService::urgencyMeta($task->due_date);

            return [
                'id' => $task->id,
                'no' => $index + 1,
                'judul' => $task->task_title,
                'deskripsi' => $task->description ?? '-',
                'mulai' => $task->start_date
                    ? Carbon::parse($task->start_date)->format('Y-m-d')
                    : '-',
                'selesai' => $task->due_date
                    ? Carbon::parse($task->due_date)->format('Y-m-d')
                    : '-',
                'pj' => $task->full_name ?? 'Belum Ditentukan',
                'assigned_to' => (int) $task->assigned_to,
                'status' => ProjectTaskService::taskStatusMeta($task->status, $task->due_date, $doneKeys),
                'days_left' => $urgency['days_left'],
                'urgency' => $urgency['urgency'],
                'urgency_label' => $urgency['label'],
                'comments' => $commentsByTask[$task->id] ?? [],
            ];
        })->toArray();

        $filters = [
            'status' => (string) $request->query('status', ''),
            'pj' => (string) $request->query('pj', ''),
            'tenggat' => (string) $request->query('tenggat', ''),
        ];

        $currentUserId = (int) Auth::id();

        // Tugas yang jatuh tempo hari ini & belum selesai — dipakai untuk banner
        // peringatan, jadi dihitung dari seluruh tugas (bukan hasil filter).
        $dueToday = array_values(array_filter(
            $tasks,
            fn (array $task) => $task['days_left'] === 0 && $task['status']['key'] !== 'selesai'
        ));

        return view('Penyusunan', [
            'id' => $project->id,
            'namaProjek' => $project->title,
            'tasks' => TaskFilter::apply($tasks, $filters),
            'dueTodayTasks' => $dueToday,
            'dueTodayMine' => array_values(array_filter(
                $dueToday,
                fn (array $task) => $task['assigned_to'] === $currentUserId
            )),
            'totalTasks' => count($tasks),
            'filterState' => $filters,
            'statusOptions' => TaskFilter::STATUS_OPTIONS,
            'tenggatOptions' => TaskFilter::DEADLINE_OPTIONS,
            'pjOptions' => TaskFilter::assigneeOptions($tasks),
            'users' => $this->tasks->assignableMembers((int) $project->id),
            'currentUserId' => $currentUserId,
        ]);
    }

    public function tambahTugas(Request $request, $id)
    {
        $request->validate([
            'judul_tugas' => 'required|string|max:255',
            'deskripsi_tugas' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'penanggung_jawab' => 'required|integer',
        ]);

        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return back()->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $assigneeId = (int) $request->penanggung_jawab;

        if (! $this->tasks->assigneeIsProjectMember((int) $project->id, $assigneeId)) {
            return back()->with('error', 'Penanggung jawab harus anggota proyek ini.');
        }

        $this->tasks->createFromPlanning(
            $project,
            $assigneeId,
            $request->judul_tugas,
            $request->deskripsi_tugas,
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            (int) Auth::id()
        );

        return back()->with(
            'success',
            'Tugas berhasil ditambahkan dan muncul di Pelaksanaan & Evaluasi (Belum Dikerjakan).'
        );
    }

    public function editTugas(Request $request, $id)
    {
        $request->validate([
            'task_id' => 'required|integer',
            'judul_tugas' => 'required|string|max:255',
            'deskripsi_tugas' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $project = Project::query()->find($id);
        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return back()->with('error', 'Akses ditolak.');
        }

        $updated = DB::table('tasks')
            ->where('id', $request->task_id)
            ->where('project_id', $project->id)
            ->update([
                'task_title' => $request->judul_tugas,
                'description' => $request->deskripsi_tugas,
                'start_date' => $request->tanggal_mulai,
                'due_date' => $request->tanggal_selesai,
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return back()->with('error', 'Tugas tidak ditemukan.');
        }

        return back()->with('success', 'Tugas berhasil diubah');
    }

    public function hapusTugas(Request $request, $id)
    {
        $request->validate(['task_id' => 'required|integer']);

        $project = Project::query()->find($id);
        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return back()->with('error', 'Akses ditolak.');
        }

        DB::table('tasks')
            ->where('id', $request->task_id)
            ->where('project_id', $project->id)
            ->delete();

        return back()->with('success', 'Tugas berhasil dihapus');
    }

    public function komentarTugas(Request $request, $id)
    {
        $request->validate([
            'task_id' => 'required|integer',
            'komentar' => 'required|string',
        ]);

        $project = Project::query()->find($id);
        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return back()->with('error', 'Akses ditolak.');
        }

        $taskExists = DB::table('tasks')
            ->where('id', $request->task_id)
            ->where('project_id', $project->id)
            ->exists();

        if (! $taskExists) {
            return back()->with('error', 'Tugas tidak ditemukan.');
        }

        DB::table('discussions')->insert([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'task_id' => $request->task_id,
            'message' => $request->komentar,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Komentar berhasil diberikan');
    }
}
