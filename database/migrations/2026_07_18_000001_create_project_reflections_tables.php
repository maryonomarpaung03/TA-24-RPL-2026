<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('project_reflection_forms', function (Blueprint $table) {
            $table->id(); $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->json('fields'); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->unique('project_id');
        });
        Schema::create('project_reflections', function (Blueprint $table) {
            $table->id(); $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id'); $table->json('answers'); $table->string('status', 16)->default('draft');
            $table->timestamp('submitted_at')->nullable(); $table->timestamps(); $table->unique(['project_id', 'student_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('project_reflections'); Schema::dropIfExists('project_reflection_forms'); }
};
