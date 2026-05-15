<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectWorkspaceService
{
    /**
     * Setup grup, anggota, milestone, dan notifikasi dosen untuk proyek baru.
     *
     * @param  list<string>  $memberEmails
     */
    public function initialize(Project $project, User $creator, string $lecturerEmail, array $memberEmails): void
    {
        $lecturerEmail = strtolower(trim($lecturerEmail));

        $project->update([
            'lecturer_email' => $lecturerEmail,
        ]);

        $this->addMember($project, $creator, 'owner');

        foreach ($this->normalizeEmails($memberEmails) as $email) {
            if ($email === strtolower($creator->email)) {
                continue;
            }

            $user = User::query()->where('email', $email)->first();

            if ($user) {
                $this->addMember($project, $user, 'member');
            }
        }

        $groupId = $this->ensureProjectGroup($project);

        DB::table('group_members')->insertOrIgnore([
            'group_id' => $groupId,
            'user_id' => $creator->id,
            'role' => 'lead',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberUserIds = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', '!=', $creator->id)
            ->pluck('user_id');

        foreach ($memberUserIds as $userId) {
            DB::table('group_members')->insertOrIgnore([
                'group_id' => $groupId,
                'user_id' => $userId,
                'role' => 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('milestones')->where('project_id', $project->id)->exists()) {
            DB::table('milestones')->insert([
                'project_id' => $project->id,
                'name' => 'Tahap awal',
                'phase' => 'perencanaan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($lecturerEmail !== '') {
            DB::table('project_notifications')->insert([
                'project_id' => $project->id,
                'recipient_email' => $lecturerEmail,
                'type' => 'project_created',
                'title' => 'Proyek baru menunggu pengajuan',
                'message' => 'Mahasiswa membuat proyek "'.$project->title.'". Proyek akan muncul untuk persetujuan setelah diajukan.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function submitToLecturer(Project $project): void
    {
        if ($project->status !== 'draft') {
            return;
        }

        $project->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        $email = strtolower(trim((string) $project->lecturer_email));

        if ($email === '') {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $project->id,
            'recipient_email' => $email,
            'type' => 'project_submitted',
            'title' => 'Pengajuan proyek baru',
            'message' => 'Proyek "'.$project->title.'" diajukan untuk persetujuan Anda.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addMember(Project $project, User $user, string $role): void
    {
        ProjectMember::query()->firstOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            [
                'email' => strtolower($user->email),
                'role' => $role,
            ]
        );
    }

    private function ensureProjectGroup(Project $project): int
    {
        $existing = DB::table('project_groups')
            ->where('project_id', $project->id)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $groupName = trim((string) ($project->group_name ?? '')) ?: $project->title;

        return (int) DB::table('project_groups')->insertGetId([
            'project_id' => $project->id,
            'group_name' => $groupName,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $emails
     * @return list<string>
     */
    private function normalizeEmails(array $emails): array
    {
        $normalized = [];

        foreach ($emails as $email) {
            $email = strtolower(trim($email));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $normalized[] = $email;
            }
        }

        return array_values(array_unique($normalized));
    }
}
