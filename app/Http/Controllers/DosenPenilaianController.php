<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\EvaluationService;
use App\Services\FinalizationService;
use App\Services\ProjectTaskService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DosenPenilaianController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluations,
        private readonly ProjectTaskService $tasks,
        private readonly FinalizationService $finalization
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

            // Finalisasi: berkas akhir yang dikirim tim, plus riwayat revisinya.
            'projectStatus' => $project->status,
            'finalSubmission' => $this->finalization->latestSubmission($id),
            'finalHistory' => $this->finalization->submissionHistory($id),
            'tasksFinalized' => ProjectAccess::isFinalized($project->status),
            'reflections' => \Illuminate\Support\Facades\DB::table('project_reflections')->leftJoin('users', 'users.id', '=', 'project_reflections.student_id')->where('project_reflections.project_id', $id)->select('project_reflections.*', 'users.full_name')->get(),
        ]);
    }

    /** Dosen meminta tim memperbaiki finalisasinya; proyek dibuka kembali. */
    public function requestRevision(Request $request, int $id)
    {
        $project = $this->authorizeProject($id);

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'Tuliskan catatan perbaikan agar mahasiswa tahu yang harus diperbaiki.',
        ]);

        try {
            $this->finalization->requestRevision($project, (int) Auth::id(), $validated['note']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()
            ->route('dosen.penilaian', $project->id)
            ->with('success', 'Permintaan revisi dikirim. Tim dapat memperbaiki dan mengirim ulang finalisasi.');
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
            ->with('success', 'Penilaian disimpan sebagai draft. Nilai belum terlihat oleh mahasiswa.');
    }

    public function publish(int $id)
    {
        $project = $this->authorizeProject($id);
        $updated = \Illuminate\Support\Facades\DB::table('project_evaluations')->where('project_id', $id)->update(['publication_status' => 'published', 'published_at' => now(), 'published_by' => Auth::id(), 'updated_at' => now()]);
        if (! $updated) return back()->with('error', 'Simpan penilaian draft terlebih dahulu.');
        $this->finalization->acceptOnGrading($project, (int) Auth::id());
        return back()->with('success', 'Nilai berhasil dipublish.');
    }

    public function unpublish(int $id)
    {
        $this->authorizeProject($id);
        \Illuminate\Support\Facades\DB::table('project_evaluations')->where('project_id', $id)->update(['publication_status' => 'draft', 'published_at' => null, 'published_by' => null, 'updated_at' => now()]);
        return back()->with('success', 'Nilai disembunyikan dan kembali menjadi draft.');
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
