<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\EvaluationService;
use App\Services\ProjectTaskService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DosenPenilaianController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluations,
        private readonly ProjectTaskService $tasks
    ) {}

    public function show(int $id)
    {
        $project = $this->authorizeProject($id);

        $members = $this->evaluations->members($id);
        $studentEvaluations = $this->evaluations->studentEvaluations($id);
        $evaluation = $this->evaluations->lecturerEvaluation($id);

        $rows = array_map(function ($member) use ($id, $studentEvaluations) {
            $existing = $studentEvaluations[$member->id] ?? null;

            return [
                'id' => $member->id,
                'name' => $member->full_name,
                'initials' => ProjectAccess::initialsFromName($member->full_name),
                'activity' => $this->evaluations->activityStats($id, $member->id),
                'score' => $existing->score ?? null,
                'feedback' => $existing->feedback ?? null,
                'criteria' => $existing->criteria ?? [],
            ];
        }, $members);

        $progress = $this->tasks->progressForProject($id);
        $pendingApproval = $this->tasks->pendingApprovalCount($id);

        return view('DosenPenilaian', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'group_name' => $project->group_name,
                'course_name' => $project->course_name,
            ],
            'components' => $this->evaluations->componentsFor($id, EvaluationService::TYPE_GROUP),
            'criteria' => $this->evaluations->componentsFor($id, EvaluationService::TYPE_INDIVIDUAL),
            'evaluation' => $evaluation,
            'students' => $rows,
            'peer' => $this->evaluations->peerSummary($id),
            'progress' => $progress,
            'pendingApproval' => $pendingApproval,
            'tasksFinalized' => $progress['total'] > 0
                && $progress['done'] === $progress['total']
                && $pendingApproval === 0,
        ]);
    }

    public function store(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $memberIds = array_map(fn ($m) => $m->id, $this->evaluations->members($id));

        $validated = $request->validate([
            'group_score' => 'required|integer|min:0|max:100',
            'components' => 'array',
            'components.*' => 'nullable|integer|min:0|max:100',
            'note' => 'nullable|string|max:1000',
            'students' => 'required|array',
            'students.*.score' => 'required|integer|min:0|max:100',
            'students.*.feedback' => 'nullable|string|max:1000',
            'students.*.criteria' => 'array',
            'students.*.criteria.*' => 'nullable|integer|min:0|max:100',
        ]);

        $students = [];

        foreach ($validated['students'] as $studentId => $data) {
            if (! in_array((int) $studentId, $memberIds, true)) {
                continue;
            }

            $students[(int) $studentId] = [
                'score' => (int) $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'criteria' => array_map('intval', array_filter($data['criteria'] ?? [], fn ($v) => $v !== null && $v !== '')),
            ];
        }

        $components = array_map('intval', array_filter(
            $validated['components'] ?? [],
            fn ($v) => $v !== null && $v !== ''
        ));

        $this->evaluations->saveLecturerEvaluation(
            $id,
            (int) Auth::id(),
            (int) $validated['group_score'],
            $components,
            $validated['note'] ?? null,
            $students
        );

        return redirect()
            ->route('dosen.penilaian', $project->id)
            ->with('success', 'Penilaian berhasil disimpan dan dikirim ke mahasiswa.');
    }

    /** Tambah komponen penilaian kelompok atau kriteria penilaian individu. */
    public function addComponent(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $validated = $request->validate([
            'type' => 'required|in:'.EvaluationService::TYPE_GROUP.','.EvaluationService::TYPE_INDIVIDUAL,
            'label' => 'required|string|max:100',
            'weight' => 'nullable|integer|min:0|max:100',
        ]);

        $this->evaluations->addComponent(
            $id,
            $validated['type'],
            $validated['label'],
            $validated['weight'] ?? null
        );

        return redirect()
            ->route('dosen.penilaian', $project->id)
            ->with('success', 'Komposisi penilaian "'.$validated['label'].'" berhasil ditambahkan.');
    }

    public function deleteComponent(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $validated = $request->validate([
            'component_id' => 'required|integer',
        ]);

        $this->evaluations->deleteComponent($id, (int) $validated['component_id']);

        return redirect()
            ->route('dosen.penilaian', $project->id)
            ->with('success', 'Komposisi penilaian berhasil dihapus.');
    }

    private function authorizeProject(int $id): Project
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView($user, $project)) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        return $project;
    }
}
