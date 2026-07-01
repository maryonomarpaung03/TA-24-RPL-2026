<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_messages')) {
            return;
        }

        Schema::table('class_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('class_messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('body');
            }
            if (! Schema::hasColumn('class_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }
            if (! Schema::hasColumn('class_messages', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_messages')) {
            return;
        }

        Schema::table('class_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']);
        });
    }
};
