<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('decomposition_submissions')) {
            return;
        }

        Schema::create('decomposition_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('submitted_by');
            $table->json('nodes_snapshot');
            $table->json('connections_snapshot');
            $table->json('comments_snapshot')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decomposition_submissions');
    }
};
