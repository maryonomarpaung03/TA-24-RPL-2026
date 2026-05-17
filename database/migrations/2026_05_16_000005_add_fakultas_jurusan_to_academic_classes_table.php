<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_classes', 'fakultas')) {
                $table->string('fakultas')->nullable()->after('lecturer_id');
            }
            if (! Schema::hasColumn('academic_classes', 'jurusan')) {
                $table->string('jurusan')->nullable()->after('fakultas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (Schema::hasColumn('academic_classes', 'jurusan')) {
                $table->dropColumn('jurusan');
            }
            if (Schema::hasColumn('academic_classes', 'fakultas')) {
                $table->dropColumn('fakultas');
            }
        });
    }
};
