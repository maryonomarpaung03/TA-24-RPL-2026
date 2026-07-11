<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengumpulan hasil tugas: mahasiswa memilih mengumpulkan berupa link atau
 * berkas (foto/dokumen) yang diunggah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'submission_type')) {
                $table->string('submission_type', 10)->nullable()->after('link');
            }

            if (! Schema::hasColumn('tasks', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('submission_type');
            }

            if (! Schema::hasColumn('tasks', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('tasks', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach (['submission_type', 'attachment_path', 'attachment_name', 'attachment_mime'] as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
