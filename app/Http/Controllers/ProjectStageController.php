<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectStageController extends Controller
{
    public function __construct(private readonly StageProgressService $stages) {}

    /** Tim menekan tombol "Finalisasi Tahap" pada tahapan yang sedang berjalan. */
    public function finalize(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $data = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', StageProgressService::ORDER)],
        ]);

        $this->stages->finalize($project, $data['stage'], (int) Auth::id());

        $next = $this->nextStage($data['stage']);
        $label = StageProgressService::label($data['stage']);

        if ($next === null) {
            return back()->with('success', 'Tahapan '.$label.' difinalisasi. Dosen telah diberi tahu bahwa refleksi siap dinilai.');
        }

        return redirect()
            ->route(StageProgressService::definitions()[$next]['route'], $id)
            ->with('success', 'Tahapan '.$label.' difinalisasi. Tahapan '.StageProgressService::label($next).' kini terbuka.');
    }

    /**
     * Tim mengklik tab tahapan berikutnya padahal tahapan berjalan belum selesai
     * dan memilih "Ya, finalisasi & lanjut" di dialog konfirmasi.
     */
    public function advance(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $data = $request->validate([
            'target' => ['required', 'string', 'in:'.implode(',', StageProgressService::ORDER)],
        ]);

        $skipped = $this->stages->currentStage((int) $project->id);

        $this->stages->finalizeAndAdvance($project, $data['target'], (int) Auth::id());

        return redirect()
            ->route(StageProgressService::definitions()[$data['target']]['route'], $id)
            ->with('success', 'Tahapan '.StageProgressService::label($skipped).' difinalisasi otomatis. Dosen telah diberi tahu.');
    }

    /** Tim meminta dosen membuka kembali tahapan tepat sebelum tahapan berjalan. */
    public function requestReopen(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $data = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', StageProgressService::ORDER)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $this->stages->requestReopen($project, $data['stage'], (int) Auth::id(), trim($data['reason']));

        return back()->with('success', 'Permintaan perbaikan tahapan '.StageProgressService::label($data['stage']).' dikirim ke dosen.');
    }

    private function authorizeProject(int $id): Project
    {
        $project = Project::query()->findOrFail($id);

        abort_unless(ProjectAccess::userCanAccess((int) Auth::id(), $project), 403);

        return $project;
    }

    private function nextStage(string $stage): ?string
    {
        $index = StageProgressService::indexOf($stage);

        return StageProgressService::ORDER[$index + 1] ?? null;
    }
}
