<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStageCompletion;
use App\Models\StageReopenRequest;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sumber kebenaran tunggal untuk urutan tahapan Computational Thinking.
 *
 * Aturannya waterfall dan simetris: tim hanya boleh maju satu tahap (dengan
 * memfinalisasi tahap berjalan), dan hanya boleh mundur satu tahap (dengan
 * meminta persetujuan dosen). Tahap yang lebih jauh — ke depan maupun ke
 * belakang — tidak bisa disentuh sama sekali.
 */
class StageProgressService
{
    public const PROBLEM = 'problem_identification';
    public const DECOMPOSITION = 'decomposition';
    public const PLANNING = 'planning';
    public const EXECUTION = 'execution';
    public const ASSESSMENT = 'assessment';

    /** @var list<string> */
    public const ORDER = [
        self::PROBLEM,
        self::DECOMPOSITION,
        self::PLANNING,
        self::EXECUTION,
        self::ASSESSMENT,
    ];

    /**
     * completions() dipanggil berkali-kali dalam satu request (tab bar, middleware,
     * panel ringkasan), jadi hasilnya di-cache per proyek.
     *
     * @var array<int, array<string, ProjectStageCompletion>>
     */
    private array $completionCache = [];

    public function __construct(private readonly ProjectTaskService $tasks) {}

    /**
     * @return array<string, array{label: string, short: string, icon: string, route: string, routes: list<string>, lecturer_route: string}>
     */
    public static function definitions(): array
    {
        return [
            self::PROBLEM => [
                'label' => 'Problem Identification',
                'short' => 'Identifikasi Masalah',
                'icon' => 'fa-search',
                'route' => 'problem-identification',
                'routes' => ['problem-identification', 'problem.*'],
                'lecturer_route' => 'dosen.problem-review',
            ],
            self::DECOMPOSITION => [
                'label' => 'Problem Decomposition',
                'short' => 'Dekomposisi',
                'icon' => 'fa-project-diagram',
                'route' => 'dekomposisi',
                'routes' => ['dekomposisi', 'dekomposisi.*'],
                'lecturer_route' => 'dosen.dekomposisi',
            ],
            self::PLANNING => [
                'label' => 'Project Planning',
                'short' => 'Penyusunan',
                'icon' => 'fa-tasks',
                'route' => 'penyusunan',
                'routes' => ['penyusunan', 'penyusunan.*'],
                'lecturer_route' => 'dosen.penyusunan',
            ],
            self::EXECUTION => [
                'label' => 'Execution & Evaluation',
                'short' => 'Pelaksanaan',
                'icon' => 'fa-play',
                'route' => 'pelaksanaan',
                'routes' => ['pelaksanaan', 'boards.store', 'waktu-progres'],
                'lecturer_route' => 'dosen.pelaksanaan',
            ],
            self::ASSESSMENT => [
                'label' => 'Assessment & Reflection',
                'short' => 'Penilaian',
                'icon' => 'fa-clipboard-check',
                'route' => 'penilaian-individu',
                'routes' => [
                    'penilaian-individu',
                    'penilaian-kelompok',
                    'penilaian-kelompok.store',
                    'penilaian-dosen-status',
                    'nilai-dari-dosen',
                ],
                'lecturer_route' => 'dosen.penilaian',
            ],
        ];
    }

    public static function label(string $stage): string
    {
        return self::definitions()[$stage]['label'] ?? $stage;
    }

    public static function indexOf(string $stage): int
    {
        $index = array_search($stage, self::ORDER, true);

        return $index === false ? -1 : $index;
    }

    /** Tahap pemilik sebuah route, atau null jika route bukan bagian dari alur CT. */
    public static function stageForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::definitions() as $stage => $def) {
            foreach ($def['routes'] as $pattern) {
                if ($routeName === $pattern) {
                    return $stage;
                }

                if (str_ends_with($pattern, '*') && str_starts_with($routeName, rtrim($pattern, '*'))) {
                    return $stage;
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, ProjectStageCompletion>
     */
    public function completions(int $projectId): array
    {
        return $this->completionCache[$projectId] ??= ProjectStageCompletion::query()
            ->with('finalizer')
            ->where('project_id', $projectId)
            ->get()
            ->keyBy('stage')
            ->all();
    }

    private function forgetCache(int $projectId): void
    {
        unset($this->completionCache[$projectId]);
    }

    public function isFinalized(int $projectId, string $stage): bool
    {
        return isset($this->completions($projectId)[$stage]);
    }

    /** Tahap pertama yang belum difinalisasi; kalau semua sudah, tetap tahap terakhir. */
    public function currentStage(int $projectId): string
    {
        $done = $this->completions($projectId);

        foreach (self::ORDER as $stage) {
            if (! isset($done[$stage])) {
                return $stage;
            }
        }

        return self::ASSESSMENT;
    }

    public function currentIndex(int $projectId): int
    {
        return self::indexOf($this->currentStage($projectId));
    }

    /** Tahap terfinalisasi & tahap berjalan boleh dibuka; sisanya tidak. */
    public function canAccess(int $projectId, string $stage): bool
    {
        return self::indexOf($stage) <= $this->currentIndex($projectId);
    }

    /**
     * Bentuk yang dipakai tab bar dan panel ringkasan.
     *
     * state: done | current | next | locked
     *
     * @return array{stages: list<array<string, mixed>>, current: string, current_index: int, all_done: bool}
     */
    public function overview(int $projectId): array
    {
        $done = $this->completions($projectId);
        $currentIndex = $this->currentIndex($projectId);
        $pending = $this->pendingReopenRequests($projectId);
        $allDone = count($done) === count(self::ORDER);

        $stages = [];

        foreach (self::ORDER as $index => $stage) {
            $completion = $done[$stage] ?? null;

            $state = match (true) {
                $completion !== null => 'done',
                $index === $currentIndex => 'current',
                $index === $currentIndex + 1 => 'next',
                default => 'locked',
            };

            $stages[] = self::definitions()[$stage] + [
                'key' => $stage,
                'index' => $index,
                'number' => $index + 1,
                'state' => $state,
                'completion' => $completion,
                'source' => $completion?->source,
                'finalized_at' => $completion?->finalized_at,
                'summary' => $completion?->summary ?? [],
                'summary_items' => $this->summaryItems($stage, (array) ($completion?->summary ?? [])),
                'finalized_by' => $completion?->finalizer?->full_name ?? $completion?->finalizer?->name,
                'can_reopen' => $this->canRequestReopen($projectId, $stage),
                'pending_reopen' => $pending[$stage] ?? null,
                // Hanya tahap berjalan yang perlu peringatan "masih kosong":
                // itulah satu-satunya tahap yang bisa difinalisasi saat ini.
                'warnings' => $state === 'current' ? $this->warningsFor($projectId, $stage) : [],
                'badge' => $this->badgeFor($state, $completion?->source, (int) ($completion?->reopen_count ?? 0)),
            ];
        }

        return [
            'stages' => $stages,
            'current' => self::ORDER[$currentIndex],
            'current_index' => $currentIndex,
            'all_done' => $allDone,
        ];
    }

    /**
     * @return array{label: string, tone: string}|null
     */
    private function badgeFor(string $state, ?string $source, int $reopenCount): ?array
    {
        if ($state !== 'done') {
            return $state === 'current' ? ['label' => 'Berjalan', 'tone' => 'blue'] : null;
        }

        if ($reopenCount > 0) {
            return ['label' => 'Perbaikan', 'tone' => 'sky'];
        }

        return match ($source) {
            'auto' => ['label' => 'Dilewati', 'tone' => 'amber'],
            'backfill' => ['label' => 'Selesai', 'tone' => 'slate'],
            default => ['label' => 'Selesai', 'tone' => 'emerald'],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Finalisasi
    |--------------------------------------------------------------------------
    */

    /**
     * Peringatan (bukan penghalang) kalau tahap difinalisasi dalam keadaan kosong.
     * Tim tetap boleh lanjut — ini hanya ditampilkan di dialog konfirmasi.
     *
     * @return list<string>
     */
    public function warningsFor(int $projectId, string $stage): array
    {
        $summary = $this->buildSummary($projectId, $stage);

        return match ($stage) {
            self::PROBLEM => ($summary['selected'] ?? null) === null
                ? ['Belum ada masalah utama yang disetujui dosen pada tahap ini.']
                : [],
            self::DECOMPOSITION => ($summary['nodes'] ?? 0) === 0
                ? ['Diagram dekomposisi masih kosong.']
                : [],
            self::PLANNING => ($summary['tasks'] ?? 0) === 0
                ? ['Belum ada tugas yang disusun.']
                : [],
            self::EXECUTION => $this->executionWarnings($summary),
            self::ASSESSMENT => ($summary['peer_filled'] ?? 0) < ($summary['members'] ?? 0)
                ? ['Belum semua anggota mengisi penilaian kelompok & refleksi.']
                : [],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return list<string>
     */
    private function executionWarnings(array $summary): array
    {
        $warnings = [];
        $total = (int) ($summary['tasks'] ?? 0);
        $backlog = (int) ($summary['backlog'] ?? 0);
        $label = (string) ($summary['backlog_label'] ?? 'Belum Dikerjakan');

        if ($backlog > 0) {
            $warnings[] = $backlog === $total
                ? 'Seluruh '.$total.' tugas masih berada di kolom "'.$label.'" dan belum dipindahkan di papan kanban.'
                : $backlog.' tugas masih berada di kolom "'.$label.'" dan belum dipindahkan di papan kanban.';
        }

        $unfinished = $total - (int) ($summary['done'] ?? 0);

        if ($unfinished > 0) {
            $warnings[] = $unfinished.' tugas belum selesai.';
        }

        if ((int) ($summary['pending_approval'] ?? 0) > 0) {
            $warnings[] = $summary['pending_approval'].' tugas masih menunggu persetujuan dosen.';
        }

        return $warnings;
    }

    /**
     * Kunci sebuah tahap. $source 'auto' dipakai saat tim melompat ke tahap
     * berikutnya tanpa menyelesaikan tahap ini.
     */
    public function finalize(Project $project, string $stage, int $userId, string $source = 'manual'): ProjectStageCompletion
    {
        $projectId = (int) $project->id;

        if (self::indexOf($stage) < 0) {
            throw ValidationException::withMessages(['stage' => 'Tahapan tidak dikenal.']);
        }

        // Assessment ditutup oleh pengiriman laporan akhir, bukan tombol finalisasi.
        if ($stage === self::ASSESSMENT) {
            throw ValidationException::withMessages([
                'stage' => 'Tahapan Assessment & Reflection ditutup dengan mengirim finalisasi proyek.',
            ]);
        }

        if ($this->isFinalized($projectId, $stage)) {
            throw ValidationException::withMessages([
                'stage' => 'Tahapan '.self::label($stage).' sudah difinalisasi.',
            ]);
        }

        if ($stage !== $this->currentStage($projectId)) {
            throw ValidationException::withMessages([
                'stage' => 'Selesaikan tahapan sebelumnya terlebih dahulu.',
            ]);
        }

        return DB::transaction(function () use ($project, $projectId, $stage, $userId, $source) {
            $completion = ProjectStageCompletion::create([
                'project_id' => $projectId,
                'stage' => $stage,
                'finalized_at' => now(),
                'finalized_by' => $userId,
                'source' => $source,
                'reopen_count' => $this->approvedReopenCount($projectId, $stage),
                'summary' => $this->buildSummary($projectId, $stage),
            ]);

            $this->forgetCache($projectId);

            $this->notifyLecturerOfFinalization($project, $stage, $source);

            return $completion;
        });
    }

    /**
     * Tim mengklik tab tahap berikutnya padahal tahap berjalan belum selesai:
     * tahap berjalan difinalisasi otomatis, lalu tahap tujuan dibuka.
     */
    public function finalizeAndAdvance(Project $project, string $target, int $userId): void
    {
        $projectId = (int) $project->id;
        $current = $this->currentStage($projectId);

        if (self::indexOf($target) !== self::indexOf($current) + 1) {
            throw ValidationException::withMessages([
                'stage' => 'Tahapan hanya dapat dilanjutkan satu langkah dari tahapan yang sedang berjalan.',
            ]);
        }

        $this->finalize($project, $current, $userId, 'auto');
    }

    /*
    |--------------------------------------------------------------------------
    | Buka kembali tahap (butuh persetujuan dosen)
    |--------------------------------------------------------------------------
    */

    /** Hanya tahap tepat satu langkah di belakang tahap berjalan yang bisa diminta dibuka. */
    public function canRequestReopen(int $projectId, string $stage): bool
    {
        if (! $this->isFinalized($projectId, $stage)) {
            return false;
        }

        if (self::indexOf($stage) !== $this->currentIndex($projectId) - 1) {
            return false;
        }

        return ! isset($this->pendingReopenRequests($projectId)[$stage]);
    }

    /**
     * @return array<string, StageReopenRequest>
     */
    public function pendingReopenRequests(int $projectId): array
    {
        return StageReopenRequest::query()
            ->where('project_id', $projectId)
            ->where('status', 'pending')
            ->get()
            ->keyBy('stage')
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, StageReopenRequest>
     */
    public function reopenRequests(int $projectId)
    {
        return StageReopenRequest::query()
            ->with('requester')
            ->where('project_id', $projectId)
            ->orderByDesc('id')
            ->get();
    }

    public function requestReopen(Project $project, string $stage, int $userId, string $reason): StageReopenRequest
    {
        $projectId = (int) $project->id;

        if (! $this->canRequestReopen($projectId, $stage)) {
            throw ValidationException::withMessages([
                'stage' => 'Hanya tahapan tepat sebelum tahapan berjalan yang dapat diminta dibuka kembali.',
            ]);
        }

        return DB::transaction(function () use ($project, $projectId, $stage, $userId, $reason) {
            $request = StageReopenRequest::create([
                'project_id' => $projectId,
                'stage' => $stage,
                'requested_by' => $userId,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            $this->notifyLecturer(
                $project,
                'stage_reopen_requested',
                'Permintaan perbaikan tahapan',
                'Tim proyek "'.$project->title.'" meminta membuka kembali tahapan '
                    .self::label($stage).'. Alasan: '.$reason
            );

            return $request;
        });
    }

    public function approveReopen(StageReopenRequest $request, Project $project, int $lecturerId, ?string $note): void
    {
        $this->assertPending($request);

        DB::transaction(function () use ($request, $project, $lecturerId, $note) {
            $request->update([
                'status' => 'approved',
                'lecturer_note' => $this->nullIfBlank($note),
                'reviewed_by' => $lecturerId,
                'reviewed_at' => now(),
            ]);

            // Kunci tahap dilepas; datanya tidak dihapus, jadi tim melanjutkan
            // dari isi terakhir dan memfinalisasinya lagi setelah diperbaiki.
            ProjectStageCompletion::query()
                ->where('project_id', $request->project_id)
                ->where('stage', $request->stage)
                ->delete();

            $this->forgetCache((int) $request->project_id);

            $this->notifyTeam(
                $project,
                'stage_reopen_approved',
                'Perbaikan tahapan disetujui',
                'Dosen membuka kembali tahapan '.self::label($request->stage).' pada proyek "'
                    .$project->title.'". Perbaiki lalu finalisasi ulang tahapan tersebut.'
                    .($this->nullIfBlank($note) ? ' Catatan: '.trim((string) $note) : '')
            );
        });
    }

    public function rejectReopen(StageReopenRequest $request, Project $project, int $lecturerId, ?string $note): void
    {
        $this->assertPending($request);

        DB::transaction(function () use ($request, $project, $lecturerId, $note) {
            $request->update([
                'status' => 'rejected',
                'lecturer_note' => $this->nullIfBlank($note),
                'reviewed_by' => $lecturerId,
                'reviewed_at' => now(),
            ]);

            $this->notifyTeam(
                $project,
                'stage_reopen_rejected',
                'Perbaikan tahapan ditolak',
                'Dosen menolak permintaan membuka kembali tahapan '.self::label($request->stage)
                    .' pada proyek "'.$project->title.'".'
                    .($this->nullIfBlank($note) ? ' Catatan: '.trim((string) $note) : '')
            );
        });
    }

    private function assertPending(StageReopenRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'stage' => 'Permintaan ini sudah diproses sebelumnya.',
            ]);
        }
    }

    private function approvedReopenCount(int $projectId, string $stage): int
    {
        return (int) StageReopenRequest::query()
            ->where('project_id', $projectId)
            ->where('stage', $stage)
            ->where('status', 'approved')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan tahap
    |--------------------------------------------------------------------------
    */

    /**
     * Dipanggil saat finalisasi lalu disimpan sebagai snapshot, sehingga ringkasan
     * yang dilihat dosen tetap mencerminkan kondisi saat tahap dikunci.
     *
     * @return array<string, mixed>
     */
    public function buildSummary(int $projectId, string $stage): array
    {
        return match ($stage) {
            self::PROBLEM => $this->problemSummary($projectId),
            self::DECOMPOSITION => $this->decompositionSummary($projectId),
            self::PLANNING => $this->planningSummary($projectId),
            self::EXECUTION => $this->executionSummary($projectId),
            self::ASSESSMENT => $this->assessmentSummary($projectId),
            default => [],
        };
    }

    /**
     * Ringkasan siap-tampil: snapshot kalau tahap sudah dikunci, hitung langsung
     * kalau belum. Dipakai bersama oleh view mahasiswa dan view dosen.
     *
     * @return array{stage: string, label: string, finalized: bool, source: ?string, finalized_at: mixed, finalized_by: ?string, items: list<array{label: string, value: string}>}
     */
    public function summaryFor(int $projectId, string $stage): array
    {
        $completion = $this->completions($projectId)[$stage] ?? null;
        $data = $completion?->summary ?: $this->buildSummary($projectId, $stage);

        return [
            'stage' => $stage,
            'label' => self::label($stage),
            'finalized' => $completion !== null,
            'source' => $completion?->source,
            'finalized_at' => $completion?->finalized_at,
            'finalized_by' => $completion?->finalizer?->full_name ?? $completion?->finalizer?->name,
            'items' => $this->summaryItems($stage, is_array($data) ? $data : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{label: string, value: string}>
     */
    private function summaryItems(string $stage, array $data): array
    {
        return match ($stage) {
            self::PROBLEM => [
                ['label' => 'Masalah terpilih', 'value' => $data['selected'] ?? 'Belum ada'],
                ['label' => 'Ide yang diajukan', 'value' => ($data['ideas'] ?? 0).' ide'],
                ['label' => 'Suara pada masalah terpilih', 'value' => ($data['votes'] ?? 0).' suara'],
            ],
            self::DECOMPOSITION => [
                ['label' => 'Jumlah node', 'value' => ($data['nodes'] ?? 0).' node'],
                ['label' => 'Kedalaman diagram', 'value' => ($data['depth'] ?? 0).' level'],
                ['label' => 'Koneksi', 'value' => ($data['connections'] ?? 0).' garis'],
            ],
            self::PLANNING => [
                ['label' => 'Total tugas', 'value' => ($data['tasks'] ?? 0).' tugas'],
                ['label' => 'Sudah ditugaskan', 'value' => ($data['assigned'] ?? 0).' tugas'],
                ['label' => 'Anggota terlibat', 'value' => ($data['members_with_task'] ?? 0).' anggota'],
            ],
            self::EXECUTION => [
                ['label' => 'Tugas selesai', 'value' => ($data['done'] ?? 0).' dari '.($data['tasks'] ?? 0)],
                ['label' => 'Menunggu persetujuan dosen', 'value' => ($data['pending_approval'] ?? 0).' tugas'],
                ['label' => 'Tugas dengan bukti', 'value' => ($data['with_submission'] ?? 0).' tugas'],
            ],
            self::ASSESSMENT => [
                ['label' => 'Refleksi & penilaian kelompok', 'value' => ($data['peer_filled'] ?? 0).' dari '.($data['members'] ?? 0).' anggota'],
                ['label' => 'Rata-rata nilai kelompok (antar anggota)', 'value' => $data['avg_group_score'] ?? '-'],
                ['label' => 'Nilai dari dosen', 'value' => ! empty($data['lecturer_scored']) ? 'Sudah dinilai' : 'Belum dinilai'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function problemSummary(int $projectId): array
    {
        $selected = DB::table('problem_identifications')
            ->where('project_id', $projectId)
            ->where('board_status', 'done')
            ->orderByDesc('updated_at')
            ->first();

        return [
            'selected' => $selected?->title,
            'ideas' => (int) DB::table('problem_identifications')->where('project_id', $projectId)->count(),
            'votes' => $selected
                ? (int) DB::table('problem_votes')->where('problem_id', $selected->id)->count()
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decompositionSummary(int $projectId): array
    {
        $nodes = DB::table('decomposition_nodes')
            ->where('project_id', $projectId)
            ->pluck('title', 'node_key');

        $connections = DB::table('decomposition_connections')
            ->where('project_id', $projectId)
            ->select('from_node', 'to_node')
            ->get();

        return [
            'nodes' => $nodes->count(),
            'connections' => $connections->count(),
            'depth' => $this->diagramDepth($nodes->keys()->all(), $connections->all()),
        ];
    }

    /**
     * Level terdalam pada diagram; siklus dijaga dengan menandai node yang sudah dikunjungi.
     *
     * @param  list<string>  $nodeKeys
     * @param  list<object>  $connections
     */
    private function diagramDepth(array $nodeKeys, array $connections): int
    {
        if ($nodeKeys === []) {
            return 0;
        }

        $children = [];
        $hasParent = [];

        foreach ($connections as $edge) {
            $children[$edge->from_node][] = $edge->to_node;
            $hasParent[$edge->to_node] = true;
        }

        $roots = array_values(array_filter($nodeKeys, fn ($key) => ! isset($hasParent[$key])));

        if ($roots === []) {
            return 1;
        }

        $depth = 0;
        $visited = [];
        $level = $roots;

        while ($level !== []) {
            $depth++;
            $next = [];

            foreach ($level as $key) {
                if (isset($visited[$key])) {
                    continue;
                }

                $visited[$key] = true;

                foreach ($children[$key] ?? [] as $child) {
                    if (! isset($visited[$child])) {
                        $next[] = $child;
                    }
                }
            }

            $level = array_unique($next);
        }

        return $depth;
    }

    /**
     * @return array<string, mixed>
     */
    private function planningSummary(int $projectId): array
    {
        $assigned = DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereNotNull('assigned_to')
            ->pluck('assigned_to');

        return [
            'tasks' => (int) DB::table('tasks')->where('project_id', $projectId)->count(),
            'assigned' => $assigned->count(),
            'members_with_task' => $assigned->unique()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function executionSummary(int $projectId): array
    {
        $progress = $this->tasks->progressForProject($projectId);
        $backlog = $this->backlogStats($projectId);

        return [
            'tasks' => (int) $progress['total'],
            'done' => (int) $progress['done'],
            'pending_approval' => $this->tasks->pendingApprovalCount($projectId),
            'with_submission' => (int) DB::table('tasks')
                ->where('project_id', $projectId)
                ->where(function ($q) {
                    $q->whereNotNull('attachment_path')->orWhereNotNull('link');
                })
                ->count(),
            'backlog' => $backlog['count'],
            'backlog_label' => $backlog['label'],
        ];
    }

    /**
     * Tugas yang masih tertahan di kolom pertama papan — belum pernah digeser tim
     * sama sekali. Tugas berstatus asing (sisa data lama) ikut dihitung di sini,
     * karena di papan pun ia jatuh ke kolom pertama.
     *
     * @return array{count: int, label: string}
     */
    private function backlogStats(int $projectId): array
    {
        $columns = $this->tasks->columnsForProject($projectId);

        if ($columns === []) {
            return ['count' => 0, 'label' => 'Belum Dikerjakan'];
        }

        $first = $columns[0];
        $validKeys = array_column($columns, 'key');

        $count = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->where(function ($q) use ($first, $validKeys) {
                $q->where('status', $first['key'])
                    ->orWhereNull('status')
                    ->orWhereNotIn('status', $validKeys);
            })
            ->count();

        return ['count' => $count, 'label' => $first['label']];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentSummary(int $projectId): array
    {
        $peers = DB::table('peer_evaluations')->where('project_id', $projectId);
        $avg = (clone $peers)->avg('group_score');

        return [
            'members' => count($this->tasks->assignableMembers($projectId)),
            'peer_filled' => (int) (clone $peers)->count(),
            'avg_group_score' => $avg !== null ? number_format((float) $avg, 1) : '-',
            'lecturer_scored' => DB::table('project_evaluations')->where('project_id', $projectId)->exists(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Progres proyek
    |--------------------------------------------------------------------------
    */

    /**
     * Persentase progres proyek: tiap tahap CT bernilai 1/5, tahap yang sudah
     * difinalisasi dihitung penuh, dan tahap yang sedang berjalan mendapat nilai
     * sebagian sesuai isinya (mis. Execution memakai rasio tugas selesai).
     */
    public function percent(int $projectId, ?string $projectStatus = null): int
    {
        if ($projectStatus === 'completed') {
            return 100;
        }

        $done = $this->completions($projectId);
        $slice = 100 / count(self::ORDER);
        $percent = count($done) * $slice;

        $current = $this->currentStage($projectId);

        if (! isset($done[$current])) {
            $percent += $slice * $this->currentStageFraction($projectId, $current);
        }

        // 100% hanya untuk proyek yang benar-benar tuntas dinilai. Tahap terakhir
        // yang isinya sudah penuh tapi belum difinalisasi berhenti di 99%.
        return (int) min(99, round($percent));
    }

    /** Seberapa jauh tahap yang sedang dikerjakan, 0.0 – 1.0. */
    private function currentStageFraction(int $projectId, string $stage): float
    {
        if ($stage === self::EXECUTION) {
            $progress = $this->tasks->progressForProject($projectId);

            return $this->ratio((int) $progress['done'], (int) $progress['total']);
        }

        return match ($stage) {
            self::PROBLEM => $this->problemFraction($projectId),
            self::DECOMPOSITION => $this->decompositionFraction($projectId),
            self::PLANNING => $this->planningFraction($projectId),
            self::ASSESSMENT => $this->ratio(
                (int) DB::table('peer_evaluations')->where('project_id', $projectId)->count(),
                count($this->tasks->assignableMembers($projectId))
            ),
            default => 0.0,
        };
    }

    /** Papan masalah bergerak idea → voting → submitted → done. */
    private function problemFraction(int $projectId): float
    {
        $statuses = DB::table('problem_identifications')
            ->where('project_id', $projectId)
            ->pluck('board_status')
            ->all();

        return match (true) {
            in_array('done', $statuses, true) => 1.0,
            in_array('submitted', $statuses, true), in_array('revision', $statuses, true) => 0.6,
            in_array('voting', $statuses, true) => 0.4,
            $statuses !== [] => 0.2,
            default => 0.0,
        };
    }

    private function decompositionFraction(int $projectId): float
    {
        $hasNodes = DB::table('decomposition_nodes')->where('project_id', $projectId)->exists();

        if (! $hasNodes) {
            return 0.0;
        }

        $submitted = DB::table('decomposition_submissions')->where('project_id', $projectId)->exists();

        return $submitted ? 0.8 : 0.4;
    }

    /** Rencana dianggap matang bila tugas sudah ada dan seluruhnya punya penanggung jawab. */
    private function planningFraction(int $projectId): float
    {
        $total = (int) DB::table('tasks')->where('project_id', $projectId)->count();

        if ($total === 0) {
            return 0.0;
        }

        $assigned = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereNotNull('assigned_to')
            ->count();

        return 0.5 + (0.5 * $this->ratio($assigned, $total));
    }

    private function ratio(int $part, int $total): float
    {
        return $total > 0 ? min(1.0, $part / $total) : 0.0;
    }

    /*
    |--------------------------------------------------------------------------
    | Finalisasi proyek (form laporan akhir)
    |--------------------------------------------------------------------------
    */

    /**
     * Tahap Assessment tidak punya tombol finalisasi sendiri — mengirim laporan
     * akhir itulah yang menutupnya. Dipanggil dari FinalizationService::submit(),
     * yang sudah mengirim notifikasi 'final_submitted' ke dosen, sehingga di sini
     * notifikasi tahap sengaja tidak dikirim lagi agar dosen tidak dapat dua kali.
     */
    public function finalizeOnFinalSubmission(Project $project, int $userId): void
    {
        $projectId = (int) $project->id;

        if ($this->isFinalized($projectId, self::ASSESSMENT)) {
            return;
        }

        ProjectStageCompletion::create([
            'project_id' => $projectId,
            'stage' => self::ASSESSMENT,
            'finalized_at' => now(),
            'finalized_by' => $userId,
            'source' => 'manual',
            'reopen_count' => 0,
            'summary' => $this->buildSummary($projectId, self::ASSESSMENT),
        ]);

        $this->forgetCache($projectId);
    }

    /*
    |--------------------------------------------------------------------------
    | Notifikasi
    |--------------------------------------------------------------------------
    */

    private function notifyLecturerOfFinalization(Project $project, string $stage, string $source): void
    {
        $label = self::label($stage);

        if ($stage === self::ASSESSMENT) {
            $this->notifyLecturer(
                $project,
                'stage_assessment_ready',
                'Refleksi selesai — siap dinilai',
                'Tim proyek "'.$project->title.'" telah mengunci refleksi & penilaian antar anggota.'
            );

            return;
        }

        if ($source === 'auto') {
            $this->notifyLecturer(
                $project,
                'stage_skipped',
                'Tahapan '.$label.' dilewati',
                'Tim proyek "'.$project->title.'" melanjutkan ke tahapan berikutnya tanpa menyelesaikan '
                    .$label.'. Tahapan tersebut difinalisasi otomatis.'
            );

            return;
        }

        $this->notifyLecturer(
            $project,
            'stage_finalized',
            'Tahapan '.$label.' diselesaikan',
            'Tim proyek "'.$project->title.'" telah memfinalisasi tahapan '.$label.'.'
        );
    }

    private function notifyLecturer(Project $project, string $type, string $title, string $message): void
    {
        $email = strtolower(trim((string) $project->lecturer_email));

        if ($email === '') {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $project->id,
            'recipient_email' => $email,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function notifyTeam(Project $project, string $type, string $title, string $message): void
    {
        $emails = DB::table('project_members')
            ->join('users', 'users.id', '=', 'project_members.user_id')
            ->where('project_members.project_id', $project->id)
            ->pluck('users.email')
            ->push(DB::table('users')->where('id', $project->created_by)->value('email'))
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique();

        foreach ($emails as $email) {
            DB::table('project_notifications')->insert([
                'project_id' => $project->id,
                'recipient_email' => $email,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /** Anggota tim mana pun boleh memfinalisasi tahap. */
    public function canFinalize(Project $project, int $userId): bool
    {
        return ProjectAccess::userCanAccess($userId, $project);
    }
}
