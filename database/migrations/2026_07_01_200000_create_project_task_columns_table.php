<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_task_columns')) {
            return;
        }

        Schema::create('project_task_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('label');
            $table->string('color', 20)->default('slate-500');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'key']);
            $table->index(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_columns');
    }
};
