<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini sudah dibuat lebih dulu oleh sync_application_schema pada
        // basis data yang berjalan; tanpa penjaga ini migrasi berikutnya ikut macet.
        if (Schema::hasTable('class_members')) {
            return;
        }

        Schema::create('class_members', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relasi ke academic_classes
            |--------------------------------------------------------------------------
            */

            $table->foreignId('academic_class_id')
            ->constrained('academic_classes')
            ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Relasi ke users
            |--------------------------------------------------------------------------
            |
            */

            $table->bigInteger('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Informasi keanggotaan
            |--------------------------------------------------------------------------
            */

            $table->timestamp('joined_at')
                ->useCurrent();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Cegah satu user terdaftar dua kali di kelas yang sama
            |--------------------------------------------------------------------------
            */

            $table->unique(
                ['academic_class_id', 'user_id'],
                'class_members_academic_class_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_members');
    }
};