<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_groups')) {
            Schema::create('project_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->string('group_name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('project_id');
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->default('member');
                $table->timestamps();

                $table->index('group_id');
                $table->index('user_id');
                $table->foreign('group_id')->references('id')->on('project_groups')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('project_groups');
    }
};
