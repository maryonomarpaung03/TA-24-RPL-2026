<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class NilaiDariDosenController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $projectId = (int) $id;
        $groupEval = $this->evaluations->lecturerEvaluation($projectId);

        if (! $groupEval) {
            return redirect()->route('penilaian-dosen-status', $projectId);
        }

        $studentEval = $this->evaluations->studentEvaluation($projectId, (int) Auth::id());

        $components = [];
        foreach ($this->evaluations->componentsFor($projectId, EvaluationService::TYPE_GROUP) as $key => $component) {
            $components[] = [
                'component' => $component['label'],
                'weight' => $component['weight'] !== null ? $component['weight'].'%' : '-',
                'score' => $groupEval->components[$key] ?? '-',
            ];
        }

        $criteria = [];
        foreach ($this->evaluations->componentsFor($projectId, EvaluationService::TYPE_INDIVIDUAL) as $key => $criterion) {
            $score = $studentEval->criteria[$key] ?? null;

            if ($score !== null) {
                $criteria[] = [
                    'criterion' => $criterion['label'],
                    'score' => $score,
                    'grade' => $this->evaluations->gradeFor($score),
                ];
            }
        }

        return view('NilaiDariDosen', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => $projectId,
            'hasEvaluation' => true,
            'penilaian' => [
                'group_score' => $groupEval->group_score,
                'group_grade' => $this->evaluations->gradeFor($groupEval->group_score),
                'group_status' => $this->evaluations->statusFor($groupEval->group_score),
                'note' => $groupEval->note,
                'evaluator' => $groupEval->lecturer_name ?? 'Dosen',
                'evaluated_at' => $groupEval->evaluated_at
                    ? Carbon::parse($groupEval->evaluated_at)->format('d M Y H:i')
                    : '-',
                'components' => $components,
                'own_score' => $studentEval->score ?? null,
                'own_grade' => $studentEval ? $this->evaluations->gradeFor($studentEval->score) : '-',
                'own_feedback' => $studentEval->feedback ?? null,
                'criteria' => $criteria,
            ],
        ]);
    }
}
