<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, completed, on-hold
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('project_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('group_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('project_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('member'); // lead, member
            $table->timestamps();
        });

        Schema::create('group_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('project_groups')->onDelete('cascade');
            $table->string('phase');
            $table->string('status')->default('pending'); // pending, in-progress, done, in-review
            $table->date('deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('ct_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('project_groups')->onDelete('cascade');
            $table->string('metric_name'); // Abstraction, Computational Logic, Problem Decomposition
            $table->decimal('score', 3, 1);
            $table->decimal('max_score', 3, 1)->default(10);
            $table->timestamps();
        });

        Schema::create('peer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('project_groups')->onDelete('cascade');
            $table->string('category'); // Technical, Reliability, Testing, Conflict Res
            $table->decimal('score', 2, 1);
            $table->timestamps();
        });

        Schema::create('group_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('project_groups')->onDelete('cascade');
            $table->foreignId('lecturer_id')->constrained('users');
            $table->text('feedback');
            $table->decimal('overall_score', 3, 1)->nullable();
            $table->string('status')->default('pending'); // pending, submitted, finalized
            $table->date('evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_evaluations');
        Schema::dropIfExists('peer_reviews');
        Schema::dropIfExists('ct_metrics');
        Schema::dropIfExists('group_milestones');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('project_groups');
        Schema::dropIfExists('projects');
    }
};
