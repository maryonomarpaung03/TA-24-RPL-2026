<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyelaraskan skema DB dengan kolom/tabel yang dipakai aplikasi.
     */
    public function up(): void
    {
        $this->syncUsersTable();
        $this->syncProjectsTable();
        $this->ensureTasksTable();
        $this->ensureMilestonesTable();
        $this->ensureDiscussionsTable();
    }

    private function syncUsersTable(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'full_name' => fn () => $table->string('full_name')->nullable()->after('name'),
                'username' => fn () => $table->string('username', 100)->nullable()->unique()->after('full_name'),
                'role' => fn () => $table->string('role', 32)->default('student')->after('password'),
                'nim' => fn () => $table->string('nim', 32)->nullable()->unique()->after('role'),
                'nidn' => fn () => $table->string('nidn', 32)->nullable()->after('nim'),
                'birth_place_date' => fn () => $table->string('birth_place_date', 255)->nullable(),
                'address' => fn () => $table->text('address')->nullable(),
                'phone' => fn () => $table->string('phone', 32)->nullable(),
                'gender' => fn () => $table->string('gender', 20)->nullable(),
                'jurusan' => fn () => $table->string('jurusan', 255)->nullable(),
                'fakultas' => fn () => $table->string('fakultas', 255)->nullable(),
                'faculty_id' => fn () => $table->unsignedBigInteger('faculty_id')->nullable(),
                'study_program_id' => fn () => $table->unsignedBigInteger('study_program_id')->nullable(),
                'profile_photo' => fn () => $table->string('profile_photo')->nullable(),
                'batch_year' => fn () => $table->unsignedSmallInteger('batch_year')->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (! Schema::hasColumn('users', $name)) {
                    $definition();
                }
            }
        });
    }

    private function syncProjectsTable(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('projects', 'problem_definition')) {
                $table->text('problem_definition')->nullable()->after('description');
            }

            if (! Schema::hasColumn('projects', 'logo')) {
                $table->string('logo')->nullable()->after('problem_definition');
            }
        });
    }

    private function ensureTasksTable(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $columns = [
                    'project_id' => fn () => $table->unsignedBigInteger('project_id')->after('id'),
                    'milestone_id' => fn () => $table->unsignedBigInteger('milestone_id')->nullable(),
                    'parent_task_id' => fn () => $table->unsignedBigInteger('parent_task_id')->nullable(),
                    'assigned_to' => fn () => $table->unsignedBigInteger('assigned_to')->nullable(),
                    'task_title' => fn () => $table->string('task_title'),
                    'description' => fn () => $table->text('description')->nullable(),
                    'priority' => fn () => $table->string('priority', 32)->default('medium'),
                    'status' => fn () => $table->string('status', 32)->default('pending'),
                    'progress_percent' => fn () => $table->unsignedTinyInteger('progress_percent')->default(0),
                    'start_date' => fn () => $table->date('start_date')->nullable(),
                    'due_date' => fn () => $table->date('due_date')->nullable(),
                    'created_at' => fn () => $table->timestamp('created_at')->nullable(),
                    'updated_at' => fn () => $table->timestamp('updated_at')->nullable(),
                ];

                foreach ($columns as $name => $definition) {
                    if (! Schema::hasColumn('tasks', $name)) {
                        $definition();
                    }
                }
            });

            return;
        }

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('task_title');
            $table->text('description')->nullable();
            $table->string('priority', 32)->default('medium');
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    private function ensureMilestonesTable(): void
    {
        if (! Schema::hasTable('milestones')) {
            Schema::create('milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('phase')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('milestones') && ! DB::table('milestones')->exists()) {
            DB::table('milestones')->insert([
                'project_id' => null,
                'name' => 'Milestone default',
                'phase' => 'umum',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function ensureDiscussionsTable(): void
    {
        if (Schema::hasTable('discussions')) {
            return;
        }

        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->text('message');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('milestones');

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                foreach (['team_id', 'problem_definition', 'logo'] as $column) {
                    if (Schema::hasColumn('projects', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
