<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectTaskService;
use App\Support\PjblContext;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PelaksanaanController extends Controller
{
    public function __construct(
        private readonly ProjectTaskService $tasks
    ) {}

    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $projectId = (int) $id;

        return view('Pelaksanaan', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => $projectId,
            'columns' => $this->tasks->columnsForProject($projectId),
            'kanban' => $this->tasks->kanbanForProject($projectId),
            'colorOptions' => ProjectTaskService::COLUMN_COLORS,
            'teamInitials' => PjblContext::memberInitials($projectId),
            'members' => $this->tasks->assignableMembers($projectId),
            'currentUserId' => (int) Auth::id(),
        ]);
    }

    public function addColumn(Request $request, $id)
    {
        $request->validate($this->columnRules());

        $project = $this->authorizeProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->createColumn(
                (int) $project->id,
                $request->label,
                (string) $request->color,
                $this->columnConfig($request)
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom berhasil ditambahkan.');
    }

    public function updateColumn(Request $request, $id)
    {
        $request->validate(['column_id' => 'required|integer'] + $this->columnRules());

        $project = $this->authorizeProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->updateColumn(
                (int) $project->id,
                (int) $request->column_id,
                $request->label,
                (string) $request->color,
                $this->columnConfig($request)
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom berhasil diperbarui.');
    }

    /**
     * @return array<string, string>
     */
    private function columnRules(): array
    {
        return [
            'label' => 'required|string|max:60',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_done' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'checklist' => 'nullable|array|max:15',
            'checklist.*' => 'nullable|string|max:200',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function columnConfig(Request $request): array
    {
        return [
            'description' => $request->input('description'),
            'is_done' => $request->boolean('is_done'),
            'requires_approval' => $request->boolean('requires_approval'),
            'checklist' => (array) $request->input('checklist', []),
        ];
    }

    public function deleteColumn(Request $request, $id)
    {
        $request->validate(['column_id' => 'required|integer']);

        $project = $this->authorizeProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->deleteColumn((int) $project->id, (int) $request->column_id);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom berhasil dihapus. Tugas dipindahkan ke kolom pertama.');
    }

    public function moveTask(Request $request, $id)
    {
        $request->validate([
            'task_id' => 'required|integer',
            'column_key' => 'required|string|max:40',
            'checklist_confirmed' => 'nullable|boolean',
        ]);

        $project = $this->authorizeProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $result = $this->tasks->moveTask(
                (int) $project->id,
                (int) $request->task_id,
                $request->column_key,
                (int) Auth::id(),
                $request->boolean('checklist_confirmed')
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with(
            'success',
            $result['pending']
                ? 'Tugas diajukan ke Dosen dan menunggu persetujuan.'
                : 'Tugas berhasil dipindahkan.'
        );
    }

    public function quickAddTask(Request $request, $id)
    {
        $request->validate([
            'column_key' => 'required|string|max:40',
            'judul_tugas' => 'required|string|max:255',
        ]);

        $project = $this->authorizeProject($id);
        if (! $project instanceof Project) {
            return $project;
        }

        try {
            $this->tasks->quickAddTask(
                (int) $project->id,
                $request->column_key,
                $request->judul_tugas,
                (int) Auth::id()
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Tugas berhasil ditambahkan.');
    }

    /**
     * @return Project|\Illuminate\Http\RedirectResponse
     */
    private function authorizeProject($id)
    {
        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return back()->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return $project;
    }
}
