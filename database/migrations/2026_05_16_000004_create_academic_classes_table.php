<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_classes')) {
            return;
        }

        Schema::create('academic_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('course_name');
            $table->string('join_code', 12)->unique();
            $table->timestamps();
        });

        Schema::create('class_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['academic_class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_members');
        Schema::dropIfExists('academic_classes');
    }
};
