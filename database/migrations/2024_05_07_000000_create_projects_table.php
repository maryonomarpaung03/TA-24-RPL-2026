<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    /*
    kalau projects sudah ada
    skip create
    */
    if (
        !Schema::hasTable(
            'projects'
        )
    ) {

        Schema::create(
            'projects',
            function (
                Blueprint $table
            ) {

                $table->id();

                $table->string(
                    'name'
                );

                $table->text(
                    'description'
                )->nullable();

                $table->string(
                    'status'
                )->default(
                    'active'
                );

                $table->date(
                    'start_date'
                );

                $table->date(
                    'end_date'
                );

                $table->foreignId(
                    'created_by'
                );

                $table->timestamps();
            }
        );
    }
}

    public function down(): void
    {
        Schema::dropIfExists('group_evaluations');
        Schema::dropIfExists('peer_reviews');
        Schema::dropIfExists('ct_metrics');
        Schema::dropIfExists('group_milestones');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('project_groups');
        Schema::dropIfExists('projects');
    }
};
