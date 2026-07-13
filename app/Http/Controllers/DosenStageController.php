<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\StageReopenRequest;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DosenStageController extends Controller
{
    public function __construct(private readonly StageProgressService $stages) {}

    /** Dosen menyetujui permintaan perbaikan: kunci tahapan dilepas untuk tim. */
    public function approve(Request $request, int $id, int $requestId)
    {
        [$project, $reopen] = $this->resolve($id, $requestId);

        $note = $request->validate([
            'lecturer_note' => ['nullable', 'string', 'max:1000'],
        ])['lecturer_note'] ?? null;

        $this->stages->approveReopen($reopen, $project, (int) Auth::id(), $note);

        return back()->with(
            'success',
            'Tahapan '.StageProgressService::label($reopen->stage).' dibuka kembali untuk tim.'
        );
    }

    public function reject(Request $request, int $id, int $requestId)
    {
        [$project, $reopen] = $this->resolve($id, $requestId);

        $note = $request->validate([
            'lecturer_note' => ['nullable', 'string', 'max:1000'],
        ])['lecturer_note'] ?? null;

        $this->stages->rejectReopen($reopen, $project, (int) Auth::id(), $note);

        return back()->with(
            'success',
            'Permintaan perbaikan tahapan '.StageProgressService::label($reopen->stage).' ditolak.'
        );
    }

    /**
     * @return array{0: Project, 1: StageReopenRequest}
     */
    private function resolve(int $id, int $requestId): array
    {
        abort_unless(Auth::user()->role === 'lecturer', 403, 'Halaman ini hanya untuk dosen.');

        $project = Project::query()->findOrFail($id);

        abort_unless(ProjectAccess::lecturerCanView(Auth::user(), $project), 403);

        $reopen = StageReopenRequest::query()
            ->where('project_id', $project->id)
            ->findOrFail($requestId);

        return [$project, $reopen];
    }
}
