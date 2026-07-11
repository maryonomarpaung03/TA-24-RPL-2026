<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penilaian kelompok oleh dosen (satu per proyek).
        if (! Schema::hasTable('project_evaluations')) {
            Schema::create('project_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('group_score');
                $table->json('components')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();

                $table->unique('project_id');
            });
        }

        // Penilaian individu oleh dosen (satu per mahasiswa per proyek).
        if (! Schema::hasTable('student_evaluations')) {
            Schema::create('student_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('score');
                $table->json('criteria')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'student_id']);
            });
        }

        // Penilaian antar anggota oleh mahasiswa (form Penilaian Kelompok).
        if (! Schema::hasTable('peer_evaluations')) {
            Schema::create('peer_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('group_score');
                $table->text('reflection')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'evaluator_id']);
            });
        }

        if (! Schema::hasTable('peer_member_scores')) {
            Schema::create('peer_member_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peer_evaluation_id')->constrained('peer_evaluations')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('score');
                $table->timestamps();

                $table->unique(['peer_evaluation_id', 'member_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_member_scores');
        Schema::dropIfExists('peer_evaluations');
        Schema::dropIfExists('student_evaluations');
        Schema::dropIfExists('project_evaluations');
    }
};
