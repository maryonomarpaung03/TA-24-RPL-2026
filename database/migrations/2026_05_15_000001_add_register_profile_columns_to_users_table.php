<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nim')) {
                $table->string('nim', 32)->nullable()->unique();
            }

            if (! Schema::hasColumn('users', 'birth_place_date')) {
                $table->string('birth_place_date', 255)->nullable();
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 32)->nullable();
            }

            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 20)->nullable();
            }

            if (! Schema::hasColumn('users', 'jurusan')) {
                $table->string('jurusan', 255)->nullable();
            }

            if (! Schema::hasColumn('users', 'fakultas')) {
                $table->string('fakultas', 255)->nullable();
            }

            if (! Schema::hasColumn('users', 'batch_year')) {
                $table->unsignedSmallInteger('batch_year')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = [
            'birth_place_date',
            'address',
            'phone',
            'gender',
            'jurusan',
            'fakultas',
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
