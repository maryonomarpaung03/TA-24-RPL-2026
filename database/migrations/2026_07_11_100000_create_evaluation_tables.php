<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void{
        // Penilaian kelompok oleh dosen
        if (! Schema::hasTable('project_evaluations')) {
            Schema::create('project_evaluations', function (Blueprint $table) {
                $table->id();

                $table->bigInteger('project_id');
                $table->bigInteger('lecturer_id');

                $table->unsignedTinyInteger('group_score');
                $table->json('components')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();

                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('cascade');

                $table->foreign('lecturer_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique('project_id');
            });
        }

        // Penilaian individu oleh dosen
        if (! Schema::hasTable('student_evaluations')) {
            Schema::create('student_evaluations', function (Blueprint $table) {
                $table->id();

                $table->bigInteger('project_id');
                $table->bigInteger('student_id');
                $table->bigInteger('lecturer_id');

                $table->unsignedTinyInteger('score');
                $table->json('criteria')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();

                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('cascade');

                $table->foreign('student_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('lecturer_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique(['project_id', 'student_id']);
            });
        }

        // Penilaian antaranggota
        if (! Schema::hasTable('peer_evaluations')) {
            Schema::create('peer_evaluations', function (Blueprint $table) {
                $table->id();

                $table->bigInteger('project_id');
                $table->bigInteger('evaluator_id');

                $table->unsignedTinyInteger('group_score');
                $table->text('reflection')->nullable();
                $table->timestamps();

                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('cascade');

                $table->foreign('evaluator_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique(['project_id', 'evaluator_id']);
            });
        }

        // Nilai setiap anggota
        if (! Schema::hasTable('peer_member_scores')) {
            Schema::create('peer_member_scores', function (Blueprint $table) {
                $table->id();

                // ID ini tetap unsigned karena peer_evaluations.id
                // dibuat menggunakan $table->id().
                $table->unsignedBigInteger('peer_evaluation_id');

                // users.id menggunakan BIGINT signed.
                $table->bigInteger('member_id');

                $table->unsignedTinyInteger('score');
                $table->timestamps();

                $table->foreign('peer_evaluation_id')
                    ->references('id')
                    ->on('peer_evaluations')
                    ->onDelete('cascade');

                $table->foreign('member_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique([
                    'peer_evaluation_id',
                    'member_id',
                ]);
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
