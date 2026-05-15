<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BuatProjekController membutuhkan tabel teams + minimal satu baris untuk team_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('teams')) {
            $this->ensureDefaultTeam();

            return;
        }

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->ensureDefaultTeam();
    }

    private function ensureDefaultTeam(): void
    {
        if (! Schema::hasTable('teams')) {
            return;
        }

        if (DB::table('teams')->exists()) {
            return;
        }

        DB::table('teams')->insert([
            'name' => 'Tim default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
