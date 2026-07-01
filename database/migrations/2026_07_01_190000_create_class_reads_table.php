<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_reads')) {
            return;
        }

        Schema::create('class_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('academic_class_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'academic_class_id']);
            $table->index('academic_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_reads');
    }
};
