<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_classes', 'academic_year')) {
                $table->string('academic_year', 20)->nullable()->after('course_name');
            }
            if (! Schema::hasColumn('academic_classes', 'semester')) {
                $table->string('semester', 20)->nullable()->after('academic_year');
            }
            if (! Schema::hasColumn('academic_classes', 'description')) {
                $table->text('description')->nullable()->after('semester');
            }
            if (! Schema::hasColumn('academic_classes', 'max_members')) {
                $table->unsignedSmallInteger('max_members')->nullable()->after('description');
            }
            if (! Schema::hasColumn('academic_classes', 'invited_student_emails')) {
                $table->json('invited_student_emails')->nullable()->after('co_lecturer_emails');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            foreach (['invited_student_emails', 'max_members', 'description', 'semester', 'academic_year'] as $column) {
                if (Schema::hasColumn('academic_classes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
