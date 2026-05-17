<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_classes', 'visibility')) {
                $table->string('visibility', 20)->default('public')->after('join_code');
            }
            if (! Schema::hasColumn('academic_classes', 'co_lecturer_emails')) {
                $table->json('co_lecturer_emails')->nullable()->after('visibility');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (Schema::hasColumn('academic_classes', 'co_lecturer_emails')) {
                $table->dropColumn('co_lecturer_emails');
            }
            if (Schema::hasColumn('academic_classes', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
