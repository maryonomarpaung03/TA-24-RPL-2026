<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Illuminate\Support\Facades\Auth;

class NilaiIndividuController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $user = PjblContext::viewer();
        $authUser = Auth::user();

        $emptyGroupSummary = [
            'overall_score' => '-',
            'grade' => '-',
            'status' => 'BELUM DINILAI',
            'evaluated_at' => '-',
            'evaluator' => '-',
        ];

        return view('NilaiIndividu', [
            'user' => $user,
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'studentData' => [
                'name' => $user['name'],
                'status' => 'ACTIVE',
                'student_id' => $authUser->nim ?? $authUser->nidn ?? '-',
                'department' => $authUser->jurusan ?? $authUser->fakultas ?? '-',
                'project' => $selected['name'],
                'role' => '-',
                'last_evaluation' => 'Belum ada penilaian',
            ],
            'assessmentMetrics' => [],
            'cumulativeAverage' => 0,
            'cumulativeGrade' => '-',
            'performanceStatus' => 'BELUM DINILAI',
            'skillsMastery' => [],
            'systemInteractions' => [],
            'lecturerFeedback' => 'Belum ada umpan balik dari dosen untuk proyek ini.',
            'submittedDate' => null,
            'groupEvaluationSummary' => $emptyGroupSummary,
            'groupEvaluationComponents' => [],
            'groupMemberScores' => [],
            'groupLecturerNote' => 'Belum ada catatan penilaian kelompok dari dosen.',
            'hasEvaluation' => false,
        ]);
    }
}
