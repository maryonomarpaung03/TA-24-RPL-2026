<?php

namespace App\Services;

use App\Support\ProjectAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationService
{
    /** Komponen penilaian kelompok beserta bobotnya (total 100%). */
    public const COMPONENTS = [
        'identifikasi' => ['label' => 'Identifikasi Masalah', 'weight' => 20],
        'dekomposisi' => ['label' => 'Dekomposisi & Perencanaan', 'weight' => 20],
        'pelaksanaan' => ['label' => 'Pelaksanaan Proyek', 'weight' => 30],
        'kolaborasi' => ['label' => 'Kolaborasi Tim', 'weight' => 15],
        'laporan' => ['label' => 'Laporan & Presentasi', 'weight' => 15],
    ];

    /** Kriteria penilaian individu. */
    public const CRITERIA = [
        'kontribusi' => 'Kontribusi Tugas',
        'kolaborasi' => 'Kolaborasi & Komunikasi',
        'kualitas' => 'Kualitas Pekerjaan',
        'ketepatan' => 'Ketepatan Waktu',
        'kritis' => 'Berpikir Kritis',
    ];

    public const TYPE_GROUP = 'group';

    public const TYPE_INDIVIDUAL = 'individual';

    public function __construct(private readonly ProjectTaskService $tasks) {}

    /**
     * Komposisi penilaian milik proyek. Kalau belum pernah diatur dosen,
     * diisi otomatis dengan komposisi bawaan.
     *
     * @return array<string, array{id: int, label: string, weight: ?int}>
     */
    public function componentsFor(int $projectId, string $type): array
    {
        $this->ensureComponents($projectId, $type);

        return DB::table('evaluation_components')
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->key => [
                    'id' => (int) $row->id,
                    'label' => $row->label,
                    'weight' => $row->weight !== null ? (int) $row->weight : null,
                ],
            ])
            ->all();
    }

    private function ensureComponents(int $projectId, string $type): void
    {
        $exists = DB::table('evaluation_components')
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return;
        }

        $defaults = $type === self::TYPE_GROUP
            ? self::COMPONENTS
            : array_map(fn ($label) => ['label' => $label, 'weight' => null], self::CRITERIA);

        $position = 0;

        foreach ($defaults as $key => $item) {
            DB::table('evaluation_components')->insert([
                'project_id' => $projectId,
                'type' => $type,
                'key' => $key,
                'label' => $item['label'],
                'weight' => $item['weight'] ?? null,
                'position' => $position++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Tambah komponen baru; key dibuat dari label agar tetap stabil. */
    public function addComponent(int $projectId, string $type, string $label, ?int $weight): void
    {
        $this->ensureComponents($projectId, $type);

        $base = Str::slug($label, '_') ?: 'komponen';
        $key = $base;
        $suffix = 2;

        while (DB::table('evaluation_components')
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->where('key', $key)
            ->exists()
        ) {
            $key = $base.'_'.$suffix++;
        }

        $position = (int) DB::table('evaluation_components')
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->max('position');

        DB::table('evaluation_components')->insert([
            'project_id' => $projectId,
            'type' => $type,
            'key' => $key,
            'label' => $label,
            'weight' => $type === self::TYPE_GROUP ? $weight : null,
            'position' => $position + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function deleteComponent(int $projectId, int $componentId): void
    {
        DB::table('evaluation_components')
            ->where('project_id', $projectId)
            ->where('id', $componentId)
            ->delete();
    }

    public function gradeFor(int|float|null $score): string
    {
        if ($score === null) {
            return '-';
        }

        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'AB',
            $score >= 75 => 'B',
            $score >= 70 => 'BC',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    public function statusFor(int|float|null $score): string
    {
        if ($score === null) {
            return 'BELUM DINILAI';
        }

        return match (true) {
            $score >= 85 => 'SANGAT BAIK',
            $score >= 75 => 'BAIK',
            $score >= 60 => 'CUKUP',
            default => 'PERLU PERBAIKAN',
        };
    }

    /** Anggota proyek (id + nama). */
    public function members(int $projectId): array
    {
        return $this->tasks->assignableMembers($projectId);
    }

    public function lecturerEvaluation(int $projectId): ?object
    {
        $row = DB::table('project_evaluations')
            ->leftJoin('users', 'users.id', '=', 'project_evaluations.lecturer_id')
            ->where('project_evaluations.project_id', $projectId)
            ->select('project_evaluations.*', 'users.full_name as lecturer_name')
            ->first();

        if ($row) {
            $row->components = $row->components ? json_decode($row->components, true) : [];
        }

        return $row;
    }

    public function studentEvaluation(int $projectId, int $studentId): ?object
    {
        $row = DB::table('student_evaluations')
            ->where('project_id', $projectId)
            ->where('student_id', $studentId)
            ->first();

        if ($row) {
            $row->criteria = $row->criteria ? json_decode($row->criteria, true) : [];
        }

        return $row;
    }

    /** @return array<int, object> student_id => evaluasi */
    public function studentEvaluations(int $projectId): array
    {
        return DB::table('student_evaluations')
            ->where('project_id', $projectId)
            ->get()
            ->keyBy('student_id')
            ->map(function ($row) {
                $row->criteria = $row->criteria ? json_decode($row->criteria, true) : [];

                return $row;
            })
            ->all();
    }

    public function peerEvaluation(int $projectId, int $evaluatorId): ?object
    {
        $row = DB::table('peer_evaluations')
            ->where('project_id', $projectId)
            ->where('evaluator_id', $evaluatorId)
            ->first();

        if ($row) {
            $row->member_scores = DB::table('peer_member_scores')
                ->where('peer_evaluation_id', $row->id)
                ->pluck('score', 'member_id')
                ->all();
        }

        return $row;
    }

    /**
     * Rekap penilaian antar anggota: rata-rata nilai yang diterima tiap anggota
     * plus rata-rata nilai kelompok versi mahasiswa.
     *
     * @return array{group_average: float|null, submitted: int, total: int, members: list<array<string, mixed>>}
     */
    public function peerSummary(int $projectId): array
    {
        $members = $this->members($projectId);

        $evaluations = DB::table('peer_evaluations')
            ->where('project_id', $projectId)
            ->get();

        $scores = DB::table('peer_member_scores')
            ->join('peer_evaluations', 'peer_evaluations.id', '=', 'peer_member_scores.peer_evaluation_id')
            ->where('peer_evaluations.project_id', $projectId)
            ->select('peer_member_scores.member_id', 'peer_member_scores.score')
            ->get()
            ->groupBy('member_id');

        return [
            'group_average' => $evaluations->isEmpty()
                ? null
                : round($evaluations->avg('group_score'), 1),
            'submitted' => $evaluations->count(),
            'total' => count($members),
            'reflections' => $evaluations->pluck('reflection')->filter()->values()->all(),
            'members' => array_map(function ($member) use ($scores) {
                $rows = $scores[$member->id] ?? collect();

                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'initials' => ProjectAccess::initialsFromName($member->full_name),
                    'average' => $rows->isEmpty() ? null : round($rows->avg('score'), 1),
                    'voters' => $rows->count(),
                ];
            }, $members),
        ];
    }

    /**
     * Aktivitas nyata mahasiswa di sistem - dipakai sebagai bahan penilaian
     * dan ditampilkan di halaman Nilai Individu.
     *
     * @return array{tasks_done: int, tasks_assigned: int, comments: int, problems: int, messages: int, on_time_percent: int, completion_percent: int}
     */
    public function activityStats(int $projectId, int $userId): array
    {
        $assigned = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->where('assigned_to', $userId)
            ->count();

        $doneKeys = DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->where('is_done_column', 1)
            ->pluck('key')
            ->all() ?: ['completed'];

        $done = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->where('assigned_to', $userId)
            ->whereIn('status', $doneKeys)
            ->count();

        $onTime = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->where('assigned_to', $userId)
            ->whereIn('status', $doneKeys)
            ->where(function ($q) {
                $q->whereNull('due_date')->orWhereColumn('updated_at', '<=', 'due_date');
            })
            ->count();

        $comments = (int) DB::table('discussions')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->count();

        $problems = (int) DB::table('problem_identifications')
            ->where('project_id', $projectId)
            ->where('created_by', $userId)
            ->count();

        $messages = (int) DB::table('project_messages')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->count();

        return [
            'tasks_assigned' => $assigned,
            'tasks_done' => $done,
            'comments' => $comments,
            'problems' => $problems,
            'messages' => $messages,
            'completion_percent' => $assigned > 0 ? (int) round($done / $assigned * 100) : 0,
            'on_time_percent' => $done > 0 ? (int) round($onTime / $done * 100) : 0,
        ];
    }

    /**
     * @param  array<string, int>  $components  key komponen => nilai
     * @param  array<int, array{score: int, feedback: ?string, criteria: array<string, int>}>  $students
     */
    public function saveLecturerEvaluation(
        int $projectId,
        int $lecturerId,
        int $groupScore,
        array $components,
        ?string $note,
        array $students
    ): void {
        DB::transaction(function () use ($projectId, $lecturerId, $groupScore, $components, $note, $students) {
            DB::table('project_evaluations')->updateOrInsert(
                ['project_id' => $projectId],
                [
                    'lecturer_id' => $lecturerId,
                    'group_score' => $groupScore,
                    'components' => json_encode($components),
                    'note' => $note,
                    'evaluated_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            foreach ($students as $studentId => $data) {
                DB::table('student_evaluations')->updateOrInsert(
                    ['project_id' => $projectId, 'student_id' => $studentId],
                    [
                        'lecturer_id' => $lecturerId,
                        'score' => $data['score'],
                        'criteria' => json_encode($data['criteria'] ?? []),
                        'feedback' => $data['feedback'] ?? null,
                        'evaluated_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $this->notifyStudents($projectId, $groupScore);
    }

    /** @param array<int, int> $memberScores member_id => nilai */
    public function savePeerEvaluation(
        int $projectId,
        int $evaluatorId,
        int $groupScore,
        array $memberScores,
        ?string $reflection
    ): void {
        DB::transaction(function () use ($projectId, $evaluatorId, $groupScore, $memberScores, $reflection) {
            DB::table('peer_evaluations')->updateOrInsert(
                ['project_id' => $projectId, 'evaluator_id' => $evaluatorId],
                [
                    'group_score' => $groupScore,
                    'reflection' => $reflection,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $evaluationId = (int) DB::table('peer_evaluations')
                ->where('project_id', $projectId)
                ->where('evaluator_id', $evaluatorId)
                ->value('id');

            foreach ($memberScores as $memberId => $score) {
                DB::table('peer_member_scores')->updateOrInsert(
                    ['peer_evaluation_id' => $evaluationId, 'member_id' => $memberId],
                    ['score' => $score, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });
    }

    private function notifyStudents(int $projectId, int $groupScore): void
    {
        $emails = DB::table('project_members')
            ->where('project_id', $projectId)
            ->pluck('email')
            ->filter()
            ->unique();

        foreach ($emails as $email) {
            DB::table('project_notifications')->insert([
                'project_id' => $projectId,
                'recipient_email' => $email,
                'type' => 'evaluation_published',
                'title' => 'Penilaian dosen sudah tersedia',
                'message' => 'Dosen telah menilai proyek Anda dengan nilai kelompok '.$groupScore.'. Lihat detailnya di menu Penilaian.',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
