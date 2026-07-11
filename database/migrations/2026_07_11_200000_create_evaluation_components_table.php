<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_components')) {
            return;
        }

        // Komposisi penilaian per proyek: 'group' = komponen penilaian kelompok,
        // 'individual' = kriteria penilaian individu.
        Schema::create('evaluation_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('key', 64);
            $table->string('label');
            $table->unsignedTinyInteger('weight')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_components');
    }
};
