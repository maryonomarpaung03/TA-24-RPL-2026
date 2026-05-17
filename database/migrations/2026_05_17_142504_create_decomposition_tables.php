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
        DECOMPOSITION NODES
        =====================================
        */
        if (!Schema::hasTable(
            'decomposition_nodes'
        )) {

            Schema::create(
                'decomposition_nodes',
                function (
                    Blueprint $table
                ) {

                    $table->id();

                    $table->unsignedBigInteger(
                        'project_id'
                    );

                    $table->string(
                        'node_key',
                        100
                    );

                    $table->string(
                        'title',
                        255
                    )->nullable();

                    $table->string(
                        'shape',
                        50
                    )->default(
                        'rounded'
                    );

                    $table->string(
                        'color',
                        20
                    )->default(
                        '#dbeafe'
                    );

                    $table->decimal(
                        'pos_x',
                        10,
                        2
                    )->default(0);

                    $table->decimal(
                        'pos_y',
                        10,
                        2
                    )->default(0);

                    $table->string(
                        'created_by',
                        255
                    )->nullable();

                    $table->string(
                        'created_at_label',
                        50
                    )->nullable();

                    $table->timestamps();

                    $table->index([
                        'project_id'
                    ]);

                    $table->unique([
                        'project_id',
                        'node_key'
                    ]);
                }
            );
        }

        /*
        =====================================
        CONNECTIONS
        =====================================
        */
        if (!Schema::hasTable(
            'decomposition_connections'
        )) {

            Schema::create(
                'decomposition_connections',
                function (
                    Blueprint $table
                ) {

                    $table->id();

                    $table->unsignedBigInteger(
                        'project_id'
                    );

                    $table->string(
                        'from_node',
                        100
                    );

                    $table->string(
                        'to_node',
                        100
                    );

                    $table->timestamps();

                    $table->index([
                        'project_id'
                    ]);
                }
            );
        }

        /*
        =====================================
        COMMENTS
        =====================================
        */
        if (!Schema::hasTable(
            'decomposition_comments'
        )) {

            Schema::create(
                'decomposition_comments',
                function (
                    Blueprint $table
                ) {

                    $table->id();

                    $table->unsignedBigInteger(
                        'project_id'
                    );

                    $table->string(
                        'author_name',
                        255
                    )->nullable();

                    $table->string(
                        'author_initials',
                        20
                    )->nullable();

                    $table->text(
                        'comment_text'
                    );

                    $table->timestamps();

                    $table->index([
                        'project_id'
                    ]);
                }
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'decomposition_comments'
        );

        Schema::dropIfExists(
            'decomposition_connections'
        );

        Schema::dropIfExists(
            'decomposition_nodes'
        );
    }
};