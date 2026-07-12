<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'submission_type')) {
                $table->string('submission_type', 20)
                    ->nullable()
                    ->after('due_date');
            }

            if (! Schema::hasColumn('tasks', 'attachment_path')) {
                $table->string('attachment_path')
                    ->nullable()
                    ->after('submission_type');
            }

            if (! Schema::hasColumn('tasks', 'attachment_name')) {
                $table->string('attachment_name')
                    ->nullable()
                    ->after('attachment_path');
            }

            if (! Schema::hasColumn('tasks', 'attachment_mime')) {
                $table->string('attachment_mime', 100)
                    ->nullable()
                    ->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $columns = [
                'submission_type',
                'attachment_path',
                'attachment_name',
                'attachment_mime',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};