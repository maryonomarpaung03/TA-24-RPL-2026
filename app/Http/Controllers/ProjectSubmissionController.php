<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectSubmissionController extends Controller
{
    public function __construct(
        private readonly ProjectWorkspaceService $workspace
    ) {}

    public function submit(Request $request, int $id)
    {
        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::userCanAccess(Auth::user(), $project)) {
            abort(403);
        }

        if ((int) $project->created_by !== (int) Auth::id()) {
            return back()->with('error', 'Hanya pembuat proyek yang dapat mengajukan ke dosen.');
        }

        if ($project->status !== 'draft') {
            if ($project->status === 'pending_approval') {
                return redirect()
                    ->route('dashboard', ['project_id' => $project->id])
                    ->with('info', 'Proyek sudah diajukan dan menunggu persetujuan dosen.');
            }

            if ($project->status === 'active') {
                return redirect()
                    ->route('dashboard', ['project_id' => $project->id])
                    ->with('info', 'Proyek sudah disetujui dosen.');
            }

            return redirect()
                ->route('dashboard', ['project_id' => $project->id])
                ->with('error', 'Proyek tidak dapat diajukan pada status saat ini.');
        }

        $this->workspace->submitToLecturer($project->fresh());

        return redirect()
            ->route('dashboard', ['project_id' => $project->id])
            ->with(
                'success',
                'Proyek berhasil diajukan ke dosen ('.$project->lecturer_email.'). Status proyek: In Review.'
            );
    }
}
