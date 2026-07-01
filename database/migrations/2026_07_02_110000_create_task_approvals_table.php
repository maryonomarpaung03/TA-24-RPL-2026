<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_approvals')) {
            return;
        }

        Schema::create('task_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('from_column_key', 40)->nullable();
            $table->string('to_column_key', 40);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->json('checklist_snapshot')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_approvals');
    }
};
