<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationPresenter
{
    public static function unreadCount(string $email): int
    {
        return (int) DB::table('project_notifications')
            ->where('recipient_email', strtolower(trim($email)))
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, object>
     */
    public static function forUser(string $email, int $limit = 50): Collection
    {
        return DB::table('project_notifications')
            ->leftJoin('projects', 'projects.id', '=', 'project_notifications.project_id')
            ->where('project_notifications.recipient_email', strtolower(trim($email)))
            ->orderByDesc('project_notifications.created_at')
            ->select(
                'project_notifications.*',
                'projects.name as project_name'
            )
            ->limit($limit)
            ->get();
    }

    public static function markRead(int $id, string $email): bool
    {
        return DB::table('project_notifications')
            ->where('id', $id)
            ->where('recipient_email', strtolower(trim($email)))
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]) > 0;
    }

    public static function markAllRead(string $email): int
    {
        return DB::table('project_notifications')
            ->where('recipient_email', strtolower(trim($email)))
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public static function openUrl(int $id): string
    {
        return route('notifikasi.open', $id);
    }

    public static function actionUrl(object $note, ?string $role): ?string
    {
        if (empty($note->project_id)) {
            return null;
        }

        $projectId = (int) $note->project_id;
        $type = (string) ($note->type ?? '');

        if ($role === 'lecturer') {
            return match (true) {
                in_array($type, ['problem_submitted_for_review', 'problem_resubmitted'], true)
                    => route('dosen.problem-review', $projectId),
                in_array($type, ['project_submitted', 'project_revision_submitted'], true)
                    => route('dosen.persetujuan.show', $projectId),
                default => route('dosen.proyek-mahasiswa.show', $projectId),
            };
        }

        return match (true) {
            $type === 'task_assigned' => route('pelaksanaan', $projectId),
            in_array($type, ['problem_approved', 'problem_revision'], true)
                => route('problem-identification', $projectId),
            in_array($type, ['project_approved', 'project_revision_approved'], true)
                => route('problem-identification', $projectId),
            default => route('problem-identification', $projectId),
        };
    }

    public static function actionLabel(object $note, ?string $role): string
    {
        $type = (string) ($note->type ?? '');

        if ($role === 'lecturer') {
            return match (true) {
                in_array($type, ['problem_submitted_for_review', 'problem_resubmitted'], true) => 'Review masalah',
                in_array($type, ['project_submitted', 'project_revision_submitted'], true) => 'Review pengajuan',
                default => 'Lihat proyek',
            };
        }

        return match (true) {
            $type === 'task_assigned' => 'Buka tugas',
            in_array($type, ['problem_approved', 'problem_revision'], true) => 'Problem Identification',
            default => 'Buka proyek',
        };
    }

    /**
     * @return array{icon: string, bg: string, text: string}
     */
    public static function styleForType(string $type): array
    {
        return match (true) {
            $type === 'task_assigned' => [
                'icon' => 'fa-tasks',
                'bg' => 'bg-violet-100',
                'text' => 'text-violet-600',
            ],
            str_contains($type, 'problem') => [
                'icon' => 'fa-lightbulb',
                'bg' => 'bg-amber-100',
                'text' => 'text-amber-600',
            ],
            str_contains($type, 'project') => [
                'icon' => 'fa-folder-open',
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-600',
            ],
            default => [
                'icon' => 'fa-bell',
                'bg' => 'bg-slate-100',
                'text' => 'text-slate-600',
            ],
        };
    }
}
