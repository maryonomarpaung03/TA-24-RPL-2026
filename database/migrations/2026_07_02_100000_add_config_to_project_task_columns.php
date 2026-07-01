<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_task_columns')) {
            return;
        }

        Schema::table('project_task_columns', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_columns', 'description')) {
                $table->text('description')->nullable()->after('label');
            }
            if (! Schema::hasColumn('project_task_columns', 'is_done_column')) {
                $table->boolean('is_done_column')->default(false)->after('color');
            }
            if (! Schema::hasColumn('project_task_columns', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('is_done_column');
            }
            if (! Schema::hasColumn('project_task_columns', 'checklist')) {
                $table->json('checklist')->nullable()->after('requires_approval');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_task_columns')) {
            return;
        }

        Schema::table('project_task_columns', function (Blueprint $table) {
            foreach (['description', 'is_done_column', 'requires_approval', 'checklist'] as $col) {
                if (Schema::hasColumn('project_task_columns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
