<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "Selesai" default (key=completed) yang dibuat sebelum fitur
     * penanda Done ada belum ter-flag. Tandai sebagai kolom selesai.
     */
    public function up(): void
    {
        if (! Schema::hasTable('project_task_columns')
            || ! Schema::hasColumn('project_task_columns', 'is_done_column')) {
            return;
        }

        DB::table('project_task_columns')
            ->where('key', 'completed')
            ->update(['is_done_column' => true]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: penanda Done tidak merusak data.
    }
};
