<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Papan Pelaksanaan mahasiswa dulu punya kanban sendiri (project_boards +
 * tasks.board_id), terpisah dari kanban dosen yang berbasis tasks.status.
 * Akibatnya tugas dari Penyusunan (yang tidak pernah diberi board_id) tidak
 * pernah muncul di papan mahasiswa.
 *
 * Sekarang kedua papan memakai satu sistem: project_task_columns + tasks.status.
 * Migrasi ini menyiapkan kolom default untuk proyek yang belum punya, merapikan
 * status tugas yang tidak cocok kolom mana pun, lalu membuang papan lama.
 */
return new class extends Migration
{
    /** @var list<array{key: string, label: string, color: string, done: bool}> */
    private const DEFAULT_COLUMNS = [
        ['key' => 'pending', 'label' => 'Belum Dikerjakan', 'color' => 'blue-600', 'done' => false],
        ['key' => 'in_progress', 'label' => 'Sedang Dikerjakan', 'color' => 'yellow-400', 'done' => false],
        ['key' => 'completed', 'label' => 'Selesai', 'color' => 'green-500', 'done' => true],
    ];

    public function up(): void
    {
        if (Schema::hasTable('project_task_columns') && Schema::hasTable('projects')) {
            $this->backfillColumns();
            $this->normalizeTaskStatuses();
        }

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'board_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                // Indeks dibuat tanpa nama eksplisit, jadi namanya konvensi Laravel.
                try {
                    $table->dropIndex('tasks_board_id_index');
                } catch (\Throwable) {
                    // Indeks sudah tidak ada (mis. SQLite): lanjut buang kolomnya.
                }

                $table->dropColumn('board_id');
            });
        }

        Schema::dropIfExists('project_boards');
    }

    /** Setiap proyek harus punya kolom kanban; tanpa ini papannya kosong. */
    private function backfillColumns(): void
    {
        $withColumns = DB::table('project_task_columns')
            ->distinct()
            ->pluck('project_id')
            ->all();

        $projectIds = DB::table('projects')
            ->whereNotIn('id', $withColumns ?: [0])
            ->pluck('id');

        foreach ($projectIds as $projectId) {
            foreach (self::DEFAULT_COLUMNS as $position => $col) {
                DB::table('project_task_columns')->insert([
                    'project_id' => $projectId,
                    'key' => $col['key'],
                    'label' => $col['label'],
                    'color' => $col['color'],
                    'is_done_column' => $col['done'],
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Papan mahasiswa dulu dikelompokkan per board_id, jadi tasks.status boleh
     * saja berisi nilai yang tidak cocok kolom mana pun. Sekarang status itulah
     * kolomnya: yang asing dijatuhkan ke kolom pertama proyeknya.
     */
    private function normalizeTaskStatuses(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        $columnsByProject = DB::table('project_task_columns')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['project_id', 'key'])
            ->groupBy('project_id');

        foreach ($columnsByProject as $projectId => $columns) {
            $keys = $columns->pluck('key')->all();
            $firstKey = $keys[0] ?? 'pending';

            DB::table('tasks')
                ->where('project_id', $projectId)
                ->where(function ($q) use ($keys) {
                    $q->whereNull('status')->orWhereNotIn('status', $keys);
                })
                ->update(['status' => $firstKey, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_boards')) {
            Schema::create('project_boards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('name', 100);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->timestamps();

                $table->index(['project_id', 'position']);
            });
        }

        // Kolom dikembalikan kosong: pemetaan tugas ke papan lama tidak disimpan.
        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'board_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('board_id')->nullable()->after('project_id');
                $table->index('board_id');
            });
        }
    }
};
