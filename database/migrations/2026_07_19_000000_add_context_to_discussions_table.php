<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('discussions', 'context')) {
            Schema::table('discussions', function (Blueprint $table) {
                $table->string('context', 20)->default('planning')->after('task_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('discussions', 'context')) {
            Schema::table('discussions', function (Blueprint $table) {
                $table->dropColumn('context');
            });
        }
    }
};
