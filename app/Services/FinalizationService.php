<?php

namespace App\Services;

use App\Models\FinalSubmission;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FinalizationService
{
    /** Tim sudah mengirim finalisasi, menunggu dosen menilai. */
    public const STATUS_REVIEW = 'pending_final_review';

    /** Dosen meminta perbaikan; tim boleh mengubah proyek lagi. */
    public const STATUS_REVISION = 'pending_final_revision';

    public function __construct(
        private readonly ProjectTaskService $tasks,
        private readonly StageProgressService $stages,
    ) {}

    /**
     * Status proyek yang berarti "berkas final sudah dikirim & terkunci".
     *
     * @return list<string>
     */
    public static function lockedStatuses(): array
    {
        return [self::STATUS_REVIEW, 'completed'];
    }

    public static function isLocked(?string $status): bool
    {
        return in_array($status, self::lockedStatuses(), true);
    }

    /**
     * Prasyarat yang dicek sistem sebelum tim boleh finalisasi. Tiap item dipakai
     * langsung oleh modal pra-finalisasi di tahapan Assessment & Reflection.
     *
     * @return array{items: list<array{key: string, label: string, detail: string, passed: bool}>, passed: bool}
     */
    public function readiness(int $projectId): array
    {
        $progress = $this->tasks->progressForProject($projectId);
        $pendingApproval = $this->tasks->pendingApprovalCount($projectId);
        $submissions = $this->submissionStats($projectId);
        $peer = $this->peerStats($projectId);

        $items = [
            [
                'key' => 'tasks_done',
                'label' => 'Semua tugas sudah selesai',
                'detail' => $progress['total'] === 0
                    ? 'Belum ada tugas pada proyek ini.'
                    : $progress['done'].' dari '.$progress['total'].' tugas selesai.',
                'passed' => $progress['total'] > 0 && $progress['done'] === $progress['total'],
            ],
            [
                'key' => 'tasks_submitted',
                'label' => 'Semua tugas punya bukti pengumpulan',
                'detail' => $submissions['with_submission'].' dari '.$submissions['total'].' tugas melampirkan berkas atau link.',
                'passed' => $submissions['total'] > 0 && $submissions['with_submission'] === $submissions['total'],
            ],
            [
                'key' => 'no_pending_approval',
                'label' => 'Tidak ada tugas yang menunggu persetujuan dosen',
                'detail' => $pendingApproval === 0
                    ? 'Tidak ada tugas yang tertahan.'
                    : $pendingApproval.' tugas masih menunggu dosen.',
                'passed' => $pendingApproval === 0,
            ],
            [
                'key' => 'peer_filled',
                'label' => 'Penilaian antar anggota sudah diisi',
                'detail' => $peer['submitted'].' dari '.$peer['total'].' anggota mengisi penilaian kelompok.',
                'passed' => $peer['total'] > 0 && $peer['submitted'] >= $peer['total'],
            ],
        ];

        return [
            'items' => $items,
            'passed' => ! in_array(false, array_column($items, 'passed'), true),
        ];
    }

    /**
     * @return array{total: int, with_submission: int}
     */
    private function submissionStats(int $projectId): array
    {
        $total = (int) DB::table('tasks')->where('project_id', $projectId)->count();

        $withSubmission = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->where(function ($q) {
                $q->whereNotNull('attachment_path')->orWhereNotNull('link');
            })
            ->count();

        return ['total' => $total, 'with_submission' => $withSubmission];
    }

    /**
     * @return array{submitted: int, total: int}
     */
    private function peerStats(int $projectId): array
    {
        return [
            'submitted' => (int) DB::table('peer_evaluations')
                ->where('project_id', $projectId)
                ->count(),
            'total' => count($this->tasks->assignableMembers($projectId)),
        ];
    }

    /** Pengiriman finalisasi terakhir (termasuk yang diminta revisi). */
    public function latestSubmission(int $projectId): ?FinalSubmission
    {
        return FinalSubmission::query()
            ->where('project_id', $projectId)
            ->latest('submitted_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, FinalSubmission>
     */
    public function submissionHistory(int $projectId)
    {
        return FinalSubmission::query()
            ->where('project_id', $projectId)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Kirim finalisasi proyek ke dosen. Prasyarat sistem dicek ulang di sini
     * supaya tidak bisa dilewati lewat request langsung ke endpoint.
     *
     * @param  array<string, mixed>  $data  report_type, report_link, presentation_link, repo_link, summary
     */
    public function submit(Project $project, int $userId, array $data, ?UploadedFile $report): FinalSubmission
    {
        if (self::isLocked($project->status)) {
            throw ValidationException::withMessages([
                'final' => 'Proyek ini sudah difinalisasi dan sedang dinilai dosen.',
            ]);
        }

        if (! $this->readiness((int) $project->id)['passed']) {
            throw ValidationException::withMessages([
                'final' => 'Prasyarat finalisasi belum terpenuhi. Periksa kembali daftar di modal finalisasi.',
            ]);
        }

        $reportType = ($data['report_type'] ?? 'file') === 'link' ? 'link' : 'file';
        $attributes = [
            'project_id' => $project->id,
            'submitted_by' => $userId,
            'report_type' => $reportType,
            'presentation_link' => $this->nullIfBlank($data['presentation_link'] ?? null),
            'repo_link' => $this->nullIfBlank($data['repo_link'] ?? null),
            'summary' => trim((string) ($data['summary'] ?? '')),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];

        if ($reportType === 'link') {
            $link = $this->nullIfBlank($data['report_link'] ?? null);

            if ($link === null) {
                throw ValidationException::withMessages([
                    'report_link' => 'Link laporan akhir wajib diisi.',
                ]);
            }

            $attributes['report_link'] = $link;
        } else {
            if (! $report) {
                throw ValidationException::withMessages([
                    'report' => 'Berkas laporan akhir wajib diunggah.',
                ]);
            }

            $attributes['report_path'] = $report->store('final_submissions/'.$project->id, 'public');
            $attributes['report_name'] = $report->getClientOriginalName();
            $attributes['report_mime'] = $report->getMimeType();
        }

        return DB::transaction(function () use ($project, $attributes, $userId) {
            $submission = FinalSubmission::create($attributes);

            $project->update(['status' => self::STATUS_REVIEW]);

            // Mengirim laporan akhir sekaligus menutup tahap Assessment & Reflection:
            // tahap itu tidak punya tombol finalisasi sendiri.
            $this->stages->finalizeOnFinalSubmission($project, $userId);

            $this->notifyLecturer($project, $submission);

            return $submission;
        });
    }

    /** Dosen meminta perbaikan: proyek dibuka kembali untuk tim. */
    public function requestRevision(Project $project, int $lecturerId, ?string $note): void
    {
        $submission = $this->latestSubmission((int) $project->id);

        if (! $submission || $project->status !== self::STATUS_REVIEW) {
            throw ValidationException::withMessages([
                'final' => 'Proyek ini tidak sedang menunggu penilaian finalisasi.',
            ]);
        }

        DB::transaction(function () use ($project, $submission, $lecturerId, $note) {
            $submission->update([
                'status' => 'revision_requested',
                'lecturer_note' => $this->nullIfBlank($note),
                'reviewed_by' => $lecturerId,
                'reviewed_at' => now(),
            ]);

            $project->update(['status' => self::STATUS_REVISION]);

            $this->notifyTeam(
                $project,
                'final_revision_requested',
                'Dosen meminta revisi finalisasi',
                'Finalisasi proyek "'.$project->title.'" perlu diperbaiki.'
                    .($this->nullIfBlank($note) ? ' Catatan: '.trim((string) $note) : '')
            );
        });
    }

    /**
     * Dipanggil saat dosen menyimpan penilaian: finalisasi diterima dan
     * proyek ditutup. Aman dipanggil untuk proyek yang tidak difinalisasi.
     */
    public function acceptOnGrading(Project $project, int $lecturerId): void
    {
        if ($project->status !== self::STATUS_REVIEW) {
            return;
        }

        $submission = $this->latestSubmission((int) $project->id);

        DB::transaction(function () use ($project, $submission, $lecturerId) {
            $submission?->update([
                'status' => 'accepted',
                'reviewed_by' => $lecturerId,
                'reviewed_at' => now(),
            ]);

            $project->update(['status' => 'completed']);
        });
    }

    private function notifyLecturer(Project $project, FinalSubmission $submission): void
    {
        $email = strtolower(trim((string) $project->lecturer_email));

        if ($email === '') {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $project->id,
            'recipient_email' => $email,
            'type' => 'final_submitted',
            'title' => 'Finalisasi proyek dikirim',
            'message' => 'Tim proyek "'.$project->title.'" telah mengirim finalisasi dan siap dinilai.',
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

    /** Hapus berkas laporan saat pengiriman lama tidak lagi dipakai. */
    public function deleteReportFile(FinalSubmission $submission): void
    {
        if ($submission->report_path) {
            Storage::disk('public')->delete($submission->report_path);
        }
    }
}
