<?php

namespace App\Http\Controllers;

use App\Services\ProjectTaskService;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;

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
            'kanban' => $this->tasks->kanbanForProject($projectId),
            'teamInitials' => PjblContext::memberInitials($projectId),
        ]);
    }
}
