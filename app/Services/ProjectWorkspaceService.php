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
     * @return list<string> Email anggota yang tidak ditemukan di sistem
     */
    public function initialize(Project $project, User $creator, string $lecturerEmail, array $memberEmails): array
    {
        $lecturerEmail = strtolower(trim($lecturerEmail));

        $project->update([
            'lecturer_email' => $lecturerEmail,
        ]);

        $skipped = $this->syncProjectMembers($project, $creator, $memberEmails);

        /*
====================================
MILESTONE DEFAULT
compatible semua schema
====================================
*/
try {

    /*
    cek apakah schema milestones
    pakai timeline_id
    */
    $hasTimelineId =
        DB::getSchemaBuilder()
        ->hasColumn(
            'milestones',
            'timeline_id'
        );

    /*
    ==================================
    SCHEMA TAPJBLCT (DB kamu)
    ==================================
    */
    if ($hasTimelineId) {

        /*
        cari timeline
        */
        $timelineId =
            DB::table(
                'timelines'
            )
            ->where(
                'project_id',
                $project->id
            )
            ->value('id');

        /*
        buat timeline jika belum ada
        */
        if (!$timelineId) {

            $timelineId =
                DB::table(
                    'timelines'
                )->insertGetId([

                    'project_id' =>
                        $project->id,

                    'created_at' =>
                        now(),
                ]);
        }

        /*
        cek milestone
        */
        $exists =
            DB::table(
                'milestones'
            )
            ->where(
                'timeline_id',
                $timelineId
            )
            ->exists();

        /*
        buat milestone default
        */
        if (!$exists) {

            DB::table(
                'milestones'
            )->insert([

                'timeline_id' =>
                    $timelineId,

                'title' =>
                    'Tahap awal',

                'description' =>
                    'Milestone awal proyek',

                'due_date' =>
                    now()
                    ->addMonth()
                    ->toDateString(),

                'status' =>
                    'pending',

                'progress_percent' =>
                    0,

                'created_at' =>
                    now(),
            ]);
        }

    }

    /*
    ==================================
    SCHEMA BARU GITHUB TIM
    ==================================
    */
    else {

        $exists =
            DB::table(
                'milestones'
            )
            ->where(
                'project_id',
                $project->id
            )
            ->exists();

        if (!$exists) {

            DB::table(
                'milestones'
            )->insert([

                'project_id' =>
                    $project->id,

                'name' =>
                    'Tahap awal',

                'phase' =>
                    'perencanaan',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
        }
    }

    } catch (
            \Throwable $e
    ) {

    \Log::error(
        'Milestone init failed',
            [
                'error' =>
                    $e->getMessage()
            ]
        );
    }

        return $skipped;
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

        $this->notifyLecturerProjectReview(
            $project,
            'project_submitted',
            'Pengajuan proyek baru',
            'Proyek "'.$project->title.'" diajukan untuk persetujuan Anda.'
        );
    }

    public function submitRevisionToLecturer(Project $project): void
    {
        if (! in_array($project->status, ['active', 'pending_revision', 'completed'], true)) {
            return;
        }

        $project->update([
            'status' => 'pending_revision',
            'submitted_at' => now(),
        ]);

        $this->notifyLecturerProjectReview(
            $project,
            'project_revision_submitted',
            'Perubahan proyek menunggu persetujuan',
            'Tim mengajukan perubahan pada proyek "'.$project->title.'". Mohon tinjau dan setujui kembali.'
        );
    }

    private function notifyLecturerProjectReview(
        Project $project,
        string $type,
        string $title,
        string $message
    ): void {
        $email = strtolower(trim((string) $project->lecturer_email));

        if ($email === '') {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $project->id,
            'recipient_email' => $email,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Sinkronkan anggota tim dari daftar email (hanya user terdaftar).
     *
     * @param  list<string>  $memberEmails
     * @return list<string> Email yang tidak ditemukan di sistem
     */
    public function syncProjectMembers(Project $project, User $creator, array $memberEmails): array
    {
        $skipped = [];
        $creatorEmail = strtolower(trim((string) $creator->email));
        $assignedUserIds = [(int) $creator->id];

        $this->addMember($project, $creator, 'owner');

        foreach ($this->normalizeEmails($memberEmails) as $email) {
            if ($email === $creatorEmail) {
                continue;
            }

            $user = $this->findUserByEmail($email);

            if ($user) {
                $this->addMember($project, $user, 'member');
                $assignedUserIds[] = (int) $user->id;
            } else {
                $skipped[] = $email;
            }
        }

        ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', '!=', $creator->id)
            ->whereNotIn('user_id', array_slice($assignedUserIds, 1))
            ->delete();

        $groupId = $this->ensureProjectGroup($project);

        DB::table('group_members')->insertOrIgnore([
            'group_id' => $groupId,
            'user_id' => $creator->id,
            'role' => 'lead',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (array_slice($assignedUserIds, 1) as $userId) {
            DB::table('group_members')->insertOrIgnore([
                'group_id' => $groupId,
                'user_id' => $userId,
                'role' => 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $skipped;
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->first();
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
