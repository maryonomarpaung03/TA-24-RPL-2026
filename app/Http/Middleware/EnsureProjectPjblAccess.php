<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Support\ProjectAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectPjblAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $request->route('id');

        if ($projectId === null) {
            return $next($request);
        }

        $project = Project::query()->find((int) $projectId);

        if (! $project) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan.');
        }

        $userId = (int) $request->user()->id;

        if (! ProjectAccess::userCanAccess($userId, $project)) {
            abort(403);
        }

        if (ProjectAccess::canAccessPjbl($project->status)) {
            return $next($request);
        }

        $message = $project->status === 'draft'
            ? 'Proyek masih berstatus draft. Selesaikan data dan ajukan ke dosen terlebih dahulu.'
            : 'Proyek belum dapat dilanjutkan karena sedang dalam review oleh dosen.';

        return redirect()
            ->route('problem-identification', $project->id)
            ->with('pjbl_locked', $message);
    }
}
