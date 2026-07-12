<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanda "sudah direview dosen" pada tiap tugas.
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('attachment_mime');
            }

            if (! Schema::hasColumn('tasks', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });

        // Finalisasi proyek: berkas akhir yang dikirim tim ke dosen.
        // Satu baris per pengiriman, sehingga revisi tersimpan sebagai riwayat.
        if (! Schema::hasTable('final_submissions')) {
            Schema::create('final_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('submitted_by');

                // Laporan akhir: berkas yang diunggah ATAU tautan (salah satu wajib).
                $table->string('report_type', 10)->default('file'); // file | link
                $table->string('report_path')->nullable();
                $table->string('report_name')->nullable();
                $table->string('report_mime')->nullable();
                $table->string('report_link')->nullable();

                $table->string('presentation_link')->nullable();
                $table->string('repo_link')->nullable();
                $table->text('summary');

                $table->string('status', 20)->default('submitted'); // submitted | revision_requested | accepted
                $table->text('lecturer_note')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('final_submissions');

        Schema::table('tasks', function (Blueprint $table) {
            foreach (['reviewed_at', 'reviewed_by'] as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
