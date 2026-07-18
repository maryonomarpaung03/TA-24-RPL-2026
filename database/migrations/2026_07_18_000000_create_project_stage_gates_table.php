<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_stage_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->string('status', 20)->default('draft'); // draft|submitted|under_review|revision|approved
            $table->unsignedInteger('revision_count')->default(0);
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('lecturer_note')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'stage']);
            $table->index(['project_id', 'status']);
        });

        Schema::table('project_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('project_evaluations', 'publication_status')) {
                $table->string('publication_status', 20)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stage_gates');
    }
};
