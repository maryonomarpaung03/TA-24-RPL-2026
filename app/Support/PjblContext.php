<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class PjblContext
{
    /**
     * @return array{name: string, role: string, initials: string, notif_count: int}
     */
    public static function viewer(): array
    {
        $user = Auth::user();
        $displayName = $user?->full_name ?? $user?->name ?? 'User';

        return [
            'name' => $displayName,
            'role' => $user?->role === 'lecturer' ? 'Dosen' : 'Mahasiswa',
            'initials' => ProjectAccess::initialsFromName($displayName),
            'notif_count' => 0,
        ];
    }

    /**
     * @return array{todo: list<array<string, mixed>>, doing: list<array<string, mixed>>, done: list<array<string, mixed>>}
     */
    public static function emptyKanban(): array
    {
        return [
            'todo' => [],
            'doing' => [],
            'done' => [],
        ];
    }

    /**
     * @return list<string>
     */
    public static function memberInitials(int $projectId): array
    {
        return ProjectAccess::memberInitials($projectId);
    }

    /**
     * @return list<string>
     */
    public static function memberNames(int $projectId): array
    {
        return array_map(
            fn (array $m) => $m['name'],
            ProjectAccess::teamMembersForProject($projectId)
        );
    }
}
