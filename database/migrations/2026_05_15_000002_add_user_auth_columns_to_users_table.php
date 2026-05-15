<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Kolom yang dipakai RegisterController / model User.
   */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 100)->nullable()->unique()->after('full_name');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('student')->after('password');
            }

            if (! Schema::hasColumn('users', 'nidn')) {
                $table->string('nidn', 32)->nullable()->after('nim');
            }

            if (! Schema::hasColumn('users', 'faculty_id')) {
                $table->unsignedBigInteger('faculty_id')->nullable()->after('fakultas');
            }

            if (! Schema::hasColumn('users', 'study_program_id')) {
                $table->unsignedBigInteger('study_program_id')->nullable()->after('faculty_id');
            }

            if (! Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('study_program_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = [
            'username',
            'role',
            'nidn',
            'faculty_id',
            'study_program_id',
            'profile_photo',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
