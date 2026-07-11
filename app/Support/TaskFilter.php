<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Filter daftar tugas (status, penanggung jawab, rentang tenggat) yang dipakai
 * bersama oleh halaman Penyusunan dan Pelaksanaan, sisi dosen maupun mahasiswa.
 *
 * Baris tugas yang difilter diharapkan punya kunci:
 * - 'status' => ['key' => ..., 'label' => ...]  (hasil ProjectTaskService::taskStatusMeta)
 * - 'assigned_to' (int) dan 'pj' (string)
 * - 'due_date' atau 'selesai' (tanggal Y-m-d atau '-')
 */
class TaskFilter
{
    public const STATUS_OPTIONS = [
        'selesai' => 'Selesai',
        'dikerjakan' => 'Sedang Dikerjakan',
        'terlewat' => 'Terlewat',
        'belum' => 'Belum Dikerjakan',
    ];

    public const DEADLINE_OPTIONS = [
        'terlewat' => 'Sudah lewat tenggat',
        'minggu_ini' => 'Jatuh tempo minggu ini',
        'bulan_ini' => 'Jatuh tempo bulan ini',
        'tanpa_tenggat' => 'Tanpa tenggat',
    ];

    /**
     * @param  list<array<string, mixed>>  $tasks
     * @param  array{status?: string, pj?: string, tenggat?: string, q?: string}  $filters
     * @return list<array<string, mixed>>
     */
    public static function apply(array $tasks, array $filters): array
    {
        $status = $filters['status'] ?? '';
        $pj = $filters['pj'] ?? '';
        $tenggat = $filters['tenggat'] ?? '';
        $keyword = trim($filters['q'] ?? '');

        $filtered = array_filter($tasks, function (array $task) use ($status, $pj, $tenggat, $keyword) {
            if ($status !== '' && ($task['status']['key'] ?? '') !== $status) {
                return false;
            }

            if ($pj !== '' && (string) ($task['assigned_to'] ?? '') !== $pj) {
                return false;
            }

            if ($keyword !== '') {
                $haystack = mb_strtolower(($task['judul'] ?? $task['name'] ?? '').' '.($task['deskripsi'] ?? $task['description'] ?? ''));

                if (! str_contains($haystack, mb_strtolower($keyword))) {
                    return false;
                }
            }

            if ($tenggat !== '' && ! self::matchesDeadline($task, $tenggat)) {
                return false;
            }

            return true;
        });

        // Nomor urut dihitung ulang agar tetap 1..n setelah difilter.
        $rows = array_values($filtered);

        foreach ($rows as $i => $row) {
            if (array_key_exists('no', $row)) {
                $rows[$i]['no'] = $i + 1;
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $task */
    private static function matchesDeadline(array $task, string $tenggat): bool
    {
        $raw = $task['due_date'] ?? $task['selesai'] ?? null;
        $due = ($raw && $raw !== '-') ? Carbon::parse($raw) : null;

        return match ($tenggat) {
            'tanpa_tenggat' => $due === null,
            'terlewat' => $due !== null
                && $due->endOfDay()->isPast()
                && ($task['status']['key'] ?? '') !== 'selesai',
            'minggu_ini' => $due !== null
                && $due->between(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()),
            'bulan_ini' => $due !== null
                && $due->between(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
            default => true,
        };
    }

    /**
     * Opsi penanggung jawab diambil dari tugas yang ada.
     *
     * @param  list<array<string, mixed>>  $tasks
     * @return array<int|string, string>
     */
    public static function assigneeOptions(array $tasks): array
    {
        $options = [];

        foreach ($tasks as $task) {
            $id = $task['assigned_to'] ?? null;

            if ($id) {
                $options[$id] = $task['pj'] ?? $task['assignee'] ?? 'Anggota';
            }
        }

        asort($options);

        return $options;
    }
}
