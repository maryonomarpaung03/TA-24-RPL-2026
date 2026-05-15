<?php

namespace App\Support;

use App\Models\Project;

class ProjectCatalog
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int|string|null $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $userId = auth()->id();

        if ($userId) {
            return ProjectAccess::resolveSelected($id, (int) $userId);
        }

        $project = Project::query()->find((int) $id);

        return $project ? ProjectAccess::toSelectedArray($project) : null;
    }

    public static function name(int|string $id): string
    {
        $row = self::find($id);

        return $row['name'] ?? 'Projek Tidak Ditemukan';
    }
}
