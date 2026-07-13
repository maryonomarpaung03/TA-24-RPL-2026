<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahapan CT dijalankan sebagai waterfall: satu tahap harus difinalisasi
     * sebelum tahap berikutnya terbuka. Dua tabel di bawah ini adalah satu-satunya
     * sumber kebenaran untuk "tahap mana yang sudah selesai" — sebelumnya status
     * itu tersebar di lima tabel berbeda dan tidak bisa dipakai untuk mengunci tab.
     */
    public function up(): void
    {
        Schema::create('project_stage_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->timestamp('finalized_at');
            $table->unsignedBigInteger('finalized_by')->nullable();

            // manual  = tim menekan tombol finalisasi tahap ini
            // auto    = tim melompat ke tahap berikutnya tanpa menyelesaikan tahap ini
            // backfill= disimpulkan dari data lama saat migration ini dijalankan
            $table->string('source', 16)->default('manual');

            $table->unsignedTinyInteger('reopen_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'stage']);
        });

        Schema::create('stage_reopen_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->unsignedBigInteger('requested_by');
            $table->text('reason');
            $table->string('status', 16)->default('pending'); // pending|approved|rejected
            $table->text('lecturer_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        $this->backfill();
    }

    /**
     * Proyek yang sudah berjalan belum punya baris di tabel baru, jadi tanpa ini
     * semuanya akan terlihat "baru mulai di Tahap 1" dan tab-nya terkunci mundur.
     * Kita simpulkan tahap tertinggi yang jelas sudah dicapai dari data lama,
     * lalu tandai semua tahap sebelumnya sebagai selesai.
     */
    private function backfill(): void
    {
        $stages = ['problem_identification', 'decomposition', 'planning', 'execution', 'assessment'];
        $now = now();
        $rows = [];

        foreach (DB::table('projects')->select('id', 'status')->get() as $project) {
            $reachedIndex = $this->reachedStageIndex((int) $project->id, (string) $project->status);

            for ($i = 0; $i < $reachedIndex; $i++) {
                $rows[] = [
                    'project_id' => $project->id,
                    'stage' => $stages[$i],
                    'finalized_at' => $now,
                    'finalized_by' => null,
                    'source' => 'backfill',
                    'reopen_count' => 0,
                    'summary' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('project_stage_completions')->insert($chunk);
        }
    }

    /** Jumlah tahap awal yang dianggap sudah selesai untuk sebuah proyek lama. */
    private function reachedStageIndex(int $projectId, string $status): int
    {
        // Sudah dikirim final / dinilai: keempat tahap sebelum assessment selesai.
        if (in_array($status, ['pending_final_review', 'pending_final_revision', 'completed'], true)) {
            return 4;
        }

        if ($this->hasRows('project_evaluations', $projectId) || $this->hasRows('peer_evaluations', $projectId)) {
            return 4;
        }

        // Sudah ada tugas: planning selesai, tim berada di execution.
        if ($this->hasRows('tasks', $projectId)) {
            return 3;
        }

        if ($this->hasRows('decomposition_submissions', $projectId)) {
            return 2;
        }

        $problemDone = Schema::hasTable('problem_identifications')
            && DB::table('problem_identifications')
                ->where('project_id', $projectId)
                ->where('board_status', 'done')
                ->exists();

        return $problemDone ? 1 : 0;
    }

    private function hasRows(string $table, int $projectId): bool
    {
        return Schema::hasTable($table)
            && DB::table($table)->where('project_id', $projectId)->exists();
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_reopen_requests');
        Schema::dropIfExists('project_stage_completions');
    }
};
