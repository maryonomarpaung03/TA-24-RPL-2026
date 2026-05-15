<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectAccess
{
    public static function userCanAccess(User|int $user, Project|int $project): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        $projectId = $project instanceof Project ? $project->id : $project;

        $row = Project::query()->find($projectId);

        if (! $row) {
            return false;
        }

        if ((int) $row->created_by === (int) $userId) {
            return true;
        }

        return ProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function lecturerCanView(User|int $user, Project|int $project): bool
    {
        $email = strtolower(trim($user instanceof User ? (string) $user->email : ''));
        $row = $project instanceof Project ? $project : Project::query()->find($project);

        if (! $row || $email === '') {
            return false;
        }

        return strtolower(trim((string) $row->lecturer_email)) === $email;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function projectsForUser(int $userId): array
    {
        $ownedIds = Project::query()
            ->where('created_by', $userId)
            ->pluck('id');

        $memberIds = ProjectMember::query()
            ->where('user_id', $userId)
            ->pluck('project_id');

        $ids = $ownedIds->merge($memberIds)->unique()->values();

        return Project::query()
            ->whereIn('id', $ids)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $p) => self::toSelectedArray($p))
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveSelected(int|string|null $projectId, int $userId): ?array
    {
        if ($projectId === null || $projectId === '') {
            return null;
        }

        $project = Project::query()->find((int) $projectId);

        if (! $project) {
            return null;
        }

        if (! self::userCanAccess($userId, $project)) {
            return null;
        }

        return self::toSelectedArray($project);
    }

    /**
     * @return array<string, mixed>
     */
    public static function toSelectedArray(Project $project): array
    {
        $status = $project->status ?? 'draft';

        return [
            'id' => $project->id,
            'name' => $project->title,
            'description' => $project->description ?? '',
            'status' => $status,
            'lecturer_email' => $project->lecturer_email,
            'lecturer_name' => $project->lecturer_name,
            'group_name' => $project->group_name,
            'course_name' => $project->course_name,
            'planned_months' => $project->planned_months,
            'submitted_at' => $project->submitted_at,
            'is_approved' => in_array($status, ['active', 'completed'], true),
            'is_pending' => $status === 'pending_approval',
            'is_draft' => $status === 'draft',
            'can_access_pjbl' => self::canAccessPjbl($status),
            'is_under_review' => $status === 'pending_approval',
            'is_in_review' => $status === 'pending_approval',
            'status_label' => match ($status) {
                'draft' => 'Draft',
                'pending_approval' => 'In Review',
                'active' => 'In Progress',
                'completed' => 'Done',
                'rejected' => 'Rejected',
                'archived' => 'Archived',
                default => 'Planning',
            },
            'created_by' => (int) $project->created_by,
        ];
    }

    public static function canAccessPjbl(?string $status): bool
    {
        return in_array($status, ['active', 'completed'], true);
    }

    /**
     * @return array{deskripsi: string, masalah: string}
     */
    public static function parseProjectDescription(?string $description): array
    {
        $description = (string) $description;
        $parts = preg_split('/\n\n--- Masalah utama ---\n/', $description, 2);

        return [
            'deskripsi' => trim($parts[0] ?? ''),
            'masalah' => trim($parts[1] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    public static function memberInitials(int $projectId): array
    {
        $rows = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $projectId)
            ->select('users.full_name', 'users.name')
            ->orderBy('project_members.id')
            ->get();

        if ($rows->isEmpty()) {
            $creator = DB::table('users')
                ->join('projects', 'projects.created_by', '=', 'users.id')
                ->where('projects.id', $projectId)
                ->select('users.full_name', 'users.name')
                ->first();

            if ($creator) {
                return [self::initialsFromName($creator->full_name ?: $creator->name)];
            }

            return [];
        }

        return $rows
            ->map(fn ($row) => self::initialsFromName($row->full_name ?: $row->name))
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * @return list<array{initials: string, name: string, role: string}>
     */
    public static function teamMembersForProject(int $projectId): array
    {
        $members = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $projectId)
            ->select('users.full_name', 'users.name', 'project_members.role')
            ->orderBy('project_members.id')
            ->get()
            ->map(fn ($row) => [
                'initials' => self::initialsFromName($row->full_name ?: $row->name),
                'name' => $row->full_name ?: $row->name ?: 'Anggota',
                'role' => $row->role === 'owner' ? 'Pembuat Proyek' : 'Anggota',
            ])
            ->values()
            ->all();

        if ($members !== []) {
            return $members;
        }

        $creator = DB::table('users')
            ->join('projects', 'projects.created_by', '=', 'users.id')
            ->where('projects.id', $projectId)
            ->select('users.full_name', 'users.name')
            ->first();

        if ($creator) {
            return [[
                'initials' => self::initialsFromName($creator->full_name ?: $creator->name),
                'name' => $creator->full_name ?: $creator->name,
                'role' => 'Pembuat Proyek',
            ]];
        }

        return [];
    }

    public static function initialsFromName(?string $display): string
    {
        $display = trim((string) $display);
        $words = preg_split('/\s+/', $display, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return 'U';
    }
}
