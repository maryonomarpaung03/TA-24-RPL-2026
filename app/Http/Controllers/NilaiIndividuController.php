<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NilaiIndividuController extends Controller
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
        $user = PjblContext::viewer();
        $authUser = Auth::user();

        $studentEval = $this->evaluations->studentEvaluation($projectId, (int) $authUser->id);
        $groupEval = $this->evaluations->lecturerEvaluation($projectId);
        $activity = $this->evaluations->activityStats($projectId, (int) $authUser->id);
        $peer = $this->evaluations->peerSummary($projectId);
        $reflectionForm = DB::table('project_reflection_forms')->where('project_id', $projectId)->value('fields');
        $reflection = DB::table('project_reflections')->where('project_id', $projectId)->where('student_id', $authUser->id)->first();

        // Kriteria individu mengikuti komposisi yang diatur dosen pada proyek ini.
        $assessmentMetrics = [];
        foreach ($this->evaluations->componentsFor($projectId, EvaluationService::TYPE_INDIVIDUAL) as $key => $criterion) {
            $score = $studentEval->criteria[$key] ?? null;

            if ($score === null) {
                continue;
            }

            $assessmentMetrics[] = [
                'criterion' => $criterion['label'],
                'score' => $score,
                'grade' => $this->evaluations->gradeFor($score),
                'performance' => $score,
            ];
        }

        $finalScore = $studentEval->score ?? null;

        $studentEvaluations = $this->evaluations->studentEvaluations($projectId);
        $groupMemberScores = array_map(fn ($m) => [
            'name' => $m->full_name,
            'score' => $studentEvaluations[$m->id]->score ?? '-',
        ], $this->evaluations->members($projectId));

        $groupComponents = [];
        foreach ($this->evaluations->componentsFor($projectId, EvaluationService::TYPE_GROUP) as $key => $component) {
            $groupComponents[] = [
                'component' => $component['label'],
                'weight' => $component['weight'] !== null ? $component['weight'].'%' : '-',
                'score' => $groupEval->components[$key] ?? '-',
            ];
        }

        return view('NilaiIndividu', [
            'user' => $user,
            'namaProjek' => $selected['name'],
            'id' => $projectId,
            'studentData' => [
                'name' => $user['name'],
                'status' => 'ACTIVE',
                'student_id' => $authUser->nim ?? $authUser->nidn ?? '-',
                'department' => $authUser->jurusan ?? $authUser->fakultas ?? '-',
                'project' => $selected['name'],
                'role' => 'Anggota',
                'last_evaluation' => $studentEval?->evaluated_at
                    ? Carbon::parse($studentEval->evaluated_at)->format('d M Y')
                    : 'Belum ada penilaian',
            ],
            'assessmentMetrics' => $assessmentMetrics,
            'cumulativeAverage' => $finalScore ?? 0,
            'cumulativeGrade' => $this->evaluations->gradeFor($finalScore),
            'performanceStatus' => $this->evaluations->statusFor($finalScore),
            'skillsMastery' => [
                ['skill' => 'Penyelesaian Tugas', 'percentage' => $activity['completion_percent']],
                ['skill' => 'Ketepatan Waktu', 'percentage' => $activity['on_time_percent']],
                ['skill' => 'Nilai Kelompok', 'percentage' => (int) ($groupEval->group_score ?? 0)],
                ['skill' => 'Nilai Rekan', 'percentage' => (int) ($this->peerAverageFor($peer, (int) $authUser->id) ?? 0)],
            ],
            'systemInteractions' => [
                ['label' => 'Tugas Diselesaikan', 'value' => $activity['tasks_done'].'/'.$activity['tasks_assigned']],
                ['label' => 'Komentar & Diskusi', 'value' => $activity['comments']],
                ['label' => 'Usulan Masalah', 'value' => $activity['problems']],
                ['label' => 'Pesan Proyek', 'value' => $activity['messages']],
            ],
            'lecturerFeedback' => $studentEval?->feedback
                ?: 'Belum ada umpan balik dari dosen untuk proyek ini.',
            'submittedDate' => $studentEval?->evaluated_at,
            'groupEvaluationSummary' => [
                'overall_score' => $groupEval->group_score ?? '-',
                'grade' => $groupEval ? $this->evaluations->gradeFor($groupEval->group_score) : '-',
                'status' => $groupEval ? $this->evaluations->statusFor($groupEval->group_score) : 'BELUM DINILAI',
                'evaluated_at' => $groupEval?->evaluated_at
                    ? Carbon::parse($groupEval->evaluated_at)->format('d M Y')
                    : '-',
                'evaluator' => $groupEval->lecturer_name ?? '-',
            ],
            'groupEvaluationComponents' => $groupEval ? $groupComponents : [],
            'groupMemberScores' => $groupEval ? $groupMemberScores : [],
            'groupLecturerNote' => $groupEval?->note
                ?: 'Belum ada catatan penilaian kelompok dari dosen.',
            'hasEvaluation' => (bool) $groupEval,
            'reflectionFields' => $reflectionForm ? json_decode($reflectionForm, true) : \App\Http\Controllers\ProjectReflectionController::defaultFields(),
            'reflectionAnswers' => $reflection?->answers ? json_decode($reflection->answers, true) : [],
            'reflectionStatus' => $reflection?->status ?? 'draft',
            'reflectionOpen' => ($groupEval->publication_status ?? 'draft') === 'published',
        ]);
    }

    private function peerAverageFor(array $peer, int $userId): ?float
    {
        foreach ($peer['members'] as $member) {
            if ($member['id'] === $userId) {
                return $member['average'];
            }
        }

        return null;
    }
}
