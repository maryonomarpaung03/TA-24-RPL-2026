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
     * Kirim finalisasi proyek ke dosen. readiness() hanya ditampilkan sebagai
     * peringatan di modal, bukan gerbang: yang mengunci pengiriman adalah
     * pernyataan tim (confirm_* divalidasi `accepted` di FinalisasiController).
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
