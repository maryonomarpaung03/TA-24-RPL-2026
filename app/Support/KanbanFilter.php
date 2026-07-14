<?php

namespace App\Support;

/**
 * Penyaring papan kanban (kolom => tugas) yang dipakai bersama oleh papan
 * mahasiswa (PelaksanaanController) dan papan dosen (DosenProjectMonitorController).
 *
 * Bentuk yang disaring adalah keluaran ProjectTaskService::kanbanForProject().
 */
class KanbanFilter
{
    /** Label prioritas mengikuti ProjectTaskService::priorityLabel(). */
    public const PRIORITY_OPTIONS = [
        'Sulit' => 'Sulit',
        'Sedang' => 'Sedang',
        'Mudah' => 'Mudah',
    ];

    /** Rentang tenggat di papan kanban dihitung dari sisa hari. */
    public const DEADLINE_OPTIONS = [
        'terlewat' => 'Sudah lewat tenggat',
        'minggu_ini' => 'Jatuh tempo ≤ 7 hari',
        'bulan_ini' => 'Jatuh tempo ≤ 30 hari',
        'tanpa_tenggat' => 'Tanpa tenggat',
    ];

    /**
     * Saring tugas di papan; kolomnya tetap utuh agar papan tidak berubah bentuk.
     *
     * @param  list<array<string, mixed>>  $kanban
     * @param  array<string, string>  $filters
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int} [papan, ditampilkan, total]
     */
    public static function apply(array $kanban, array $filters): array
    {
        $total = 0;
        $shown = 0;

        foreach ($kanban as $i => $column) {
            $tasks = $column['tasks'] ?? [];
            $total += count($tasks);
            $isDoneColumn = (bool) ($column['is_done'] ?? false);

            $kept = array_values(array_filter(
                $tasks,
                fn (array $task) => self::matches($task, $filters, $isDoneColumn)
            ));

            $shown += count($kept);
            $kanban[$i]['tasks'] = $kept;
        }

        return [$kanban, $shown, $total];
    }

    /**
     * @param  array<string, mixed>  $task
     * @param  array<string, string>  $filters
     */
    private static function matches(array $task, array $filters, bool $isDoneColumn): bool
    {
        if (($filters['pj'] ?? '') !== '' && (string) ($task['assigned_to'] ?? '') !== $filters['pj']) {
            return false;
        }

        if (($filters['prioritas'] ?? '') !== '' && ($task['level'] ?? '') !== $filters['prioritas']) {
            return false;
        }

        if (($filters['q'] ?? '') !== '') {
            $haystack = mb_strtolower(($task['name'] ?? '').' '.($task['description'] ?? ''));

            if (! str_contains($haystack, mb_strtolower($filters['q']))) {
                return false;
            }
        }

        $daysLeft = $task['days_left'] ?? null;

        return match ($filters['tenggat'] ?? '') {
            // Tugas yang sudah di kolom Selesai tidak dihitung terlewat.
            'terlewat' => ! $isDoneColumn && $daysLeft !== null && $daysLeft < 0,
            'tanpa_tenggat' => ($task['due_date'] ?? null) === null,
            'minggu_ini' => $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7,
            'bulan_ini' => $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30,
            default => true,
        };
    }

    /**
     * Semua tugas di papan sebagai satu daftar datar.
     *
     * @param  list<array<string, mixed>>  $kanban
     * @return list<array<string, mixed>>
     */
    public static function flatten(array $kanban): array
    {
        $tasks = [];

        foreach ($kanban as $column) {
            foreach ($column['tasks'] ?? [] as $task) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }
}
