<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'group_name')) {
                $table->string('group_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('projects', 'course_name')) {
                $table->string('course_name')->nullable()->after('group_name');
            }

            if (! Schema::hasColumn('projects', 'lecturer_name')) {
                $table->string('lecturer_name')->nullable()->after('lecturer_email');
            }

            if (! Schema::hasColumn('projects', 'planned_months')) {
                $table->unsignedTinyInteger('planned_months')->nullable()->after('end_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        foreach (['group_name', 'course_name', 'lecturer_name', 'planned_months'] as $column) {
            if (Schema::hasColumn('projects', $column)) {
                Schema::table('projects', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
