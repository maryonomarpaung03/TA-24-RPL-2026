<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discussions')) {
            return;
        }

        if (! Schema::hasColumn('discussions', 'parent_id')) {
            Schema::table('discussions', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('problem_id');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('discussions')
            && Schema::hasColumn('discussions', 'parent_id')
        ) {
            Schema::table('discussions', function (Blueprint $table) {
                $table->dropColumn('parent_id');
            });
        }
    }
};
