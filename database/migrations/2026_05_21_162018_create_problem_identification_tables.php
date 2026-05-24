<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        =====================================
        PROBLEM IDENTIFICATION
        =====================================
        */
        if (!Schema::hasTable(
            'problem_identifications'
        )) {

            Schema::create(
                'problem_identifications',
                function (
                    Blueprint $table
                ) {

                    $table->id();

                    $table->unsignedBigInteger(
                        'project_id'
                    );

                    $table->unsignedBigInteger(
                        'created_by'
                    );

                    /*
                    form input
                    */
                    $table->string(
                        'title',
                        255
                    );

                    $table->text(
                        'description'
                    )->nullable();

                    $table->enum(
                        'category',
                        [
                            'Teknik',
                            'Diskusi',
                            'Etika',
                            'Kebutuhan Proyek'
                        ]
                    )->default(
                        'Teknik'
                    );

                    $table->enum(
                        'priority',
                        [
                            'Rendah',
                            'Sedang',
                            'Tinggi'
                        ]
                    )->default(
                        'Sedang'
                    );

                    /*
                    attachment link
                    */
                    $table->text(
                        'attachment_link'
                    )->nullable();

                    /*
                    kanban status
                    */
                    $table->enum(
                        'board_status',
                        [
                            'idea',
                            'voting',
                            'submitted',
                            'revision',
                            'done'
                        ]
                    )->default(
                        'idea'
                    );

                    /*
                    voting
                    */
                    $table->boolean(
                        'voting_open'
                    )->default(false);

                    $table->timestamp(
                        'voting_deadline'
                    )->nullable();

                    /*
                    feedback dosen
                    */
                    $table->text(
                        'lecturer_feedback'
                    )->nullable();

                    $table->timestamps();

                    $table->index([
                        'project_id'
                    ]);
                }
            );
        }

        /*
        =====================================
        VOTING
        =====================================
        */
        if (!Schema::hasTable(
            'problem_votes'
        )) {

            Schema::create(
                'problem_votes',
                function (
                    Blueprint $table
                ) {

                    $table->id();

                    $table->unsignedBigInteger(
                        'problem_id'
                    );

                    $table->unsignedBigInteger(
                        'project_id'
                    );

                    $table->unsignedBigInteger(
                        'user_id'
                    );

                    /*
                    only 1 vote
                    */
                    $table->timestamps();

                    $table->unique([
                        'project_id',
                        'user_id'
                    ]);
                }
            );
        }

        /*
        =====================================
        COMMENTS
        =====================================
        */
        if (!Schema::hasColumn(
            'discussions',
            'problem_id'
        )) {

            Schema::table(
                'discussions',
                function (
                    Blueprint $table
                ) {

                    $table->unsignedBigInteger(
                        'problem_id'
                    )->nullable()
                    ->after(
                        'task_id'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'problem_votes'
        );

        Schema::dropIfExists(
            'problem_identifications'
        );

        if (
            Schema::hasColumn(
                'discussions',
                'problem_id'
            )
        ) {

            Schema::table(
                'discussions',
                function (
                    Blueprint $table
                ) {

                    $table->dropColumn(
                        'problem_id'
                    );
                }
            );
        }
    }
};