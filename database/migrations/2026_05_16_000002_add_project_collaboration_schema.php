<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'lecturer_email')) {
                    $table->string('lecturer_email')->nullable()->after('created_by');
                }

                if (! Schema::hasColumn('projects', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('status');
                }
            });
        }

        if (! Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('email');
                $table->string('role', 32)->default('member');
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('project_notifications')) {
            Schema::create('project_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('recipient_email');
                $table->string('type', 64);
                $table->string('title');
                $table->text('message')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notifications');
        Schema::dropIfExists('project_members');

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                foreach (['lecturer_email', 'submitted_at'] as $column) {
                    if (Schema::hasColumn('projects', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
