<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || Schema::hasColumn('projects', 'academic_class_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_class_id')->nullable();
            $table->index('academic_class_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'academic_class_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['academic_class_id']);
            $table->dropColumn('academic_class_id');
        });
    }
};
