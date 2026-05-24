<?php

namespace App\Services;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\DB;

class ProjectTaskService
{
    public const STATUS_TODO = 'pending';

    public const STATUS_DOING = 'in_progress';

    public const STATUS_DONE = 'completed';

    /**
     * @return array{todo: list<array<string, mixed>>, doing: list<array<string, mixed>>, done: list<array<string, mixed>>}
     */
    public function kanbanForProject(int $projectId): array
    {
        $kanban = [
            'todo' => [],
            'doing' => [],
            'done' => [],
        ];

        $rows = DB::table('tasks')
            ->leftJoin('users', 'users.id', '=', 'tasks.assigned_to')
            ->where('tasks.project_id', $projectId)
            ->orderBy('tasks.created_at')
            ->select(
                'tasks.id',
                'tasks.task_title',
                'tasks.description',
                'tasks.priority',
                'tasks.status',
                'tasks.due_date',
                'users.full_name',
                'users.name as user_name'
            )
            ->get();

        foreach ($rows as $row) {
            $column = $this->kanbanColumnForStatus((string) $row->status);
            $assigneeName = $row->full_name ?: $row->user_name ?: 'Tim';

            $kanban[$column][] = [
                'id' => $row->id,
                'name' => $row->task_title,
                'description' => $row->description ?? '',
                'creator' => ProjectAccess::initialsFromName($assigneeName),
                'assignee' => $assigneeName,
                'level' => $this->priorityLabel((string) $row->priority),
                'due_date' => $row->due_date,
            ];
        }

        return $kanban;
    }

    /**
     * @return array{id: int}
     */
    public function createFromPlanning(
        Project $project,
        int $assigneeId,
        string $title,
        ?string $description,
        string $startDate,
        string $dueDate,
        int $createdByUserId
    ): array {
        $milestoneId = $this->ensureMilestoneId((int) $project->id);

        $taskId = (int) DB::table('tasks')->insertGetId([
            'project_id' => $project->id,
            'milestone_id' => $milestoneId,
            'parent_task_id' => null,
            'assigned_to' => $assigneeId,
            'task_title' => $title,
            'description' => $description,
            'priority' => 'medium',
            'status' => self::STATUS_TODO,
            'progress_percent' => 0,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($assigneeId !== $createdByUserId) {
            $this->notifyAssignee($project, $assigneeId, $title, $createdByUserId);
        }

        return ['id' => $taskId];
    }

    public function notifyAssignee(
        Project $project,
        int $assigneeUserId,
        string $taskTitle,
        int $assignedByUserId
    ): void {
        $assignee = DB::table('users')->where('id', $assigneeUserId)->first();
        if (! $assignee || empty($assignee->email)) {
            return;
        }

        $assigner = DB::table('users')->where('id', $assignedByUserId)->first();
        $assignerName = $assigner->full_name ?? $assigner->name ?? 'Rekan tim';

        DB::table('project_notifications')->insert([
            'project_id' => $project->id,
            'recipient_email' => strtolower(trim((string) $assignee->email)),
            'type' => 'task_assigned',
            'title' => 'Tugas baru ditugaskan kepada Anda',
            'message' => $assignerName.' menugaskan "'.$taskTitle.'" kepada Anda di proyek "'.$project->title.'".',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function assigneeIsProjectMember(int $projectId, int $userId): bool
    {
        if ((int) Project::query()->where('id', $projectId)->value('created_by') === $userId) {
            return true;
        }

        return DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @return list<object{id: int, full_name: string}>
     */
    public function assignableMembers(int $projectId): array
    {
        $creatorId = (int) (Project::query()->where('id', $projectId)->value('created_by') ?? 0);

        $memberIds = DB::table('project_members')
            ->where('project_id', $projectId)
            ->pluck('user_id')
            ->push($creatorId)
            ->unique()
            ->filter()
            ->values();

        return DB::table('users')
            ->whereIn('id', $memberIds)
            ->orderBy('full_name')
            ->select('id', 'full_name', 'name')
            ->get()
            ->map(fn ($u) => (object) [
                'id' => $u->id,
                'full_name' => $u->full_name ?: $u->name ?: 'Anggota',
            ])
            ->values()
            ->all();
    }

    private function ensureMilestoneId(int $projectId): int
    {
        $existing = DB::table('milestones')
            ->where('project_id', $projectId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $fallback = DB::table('milestones')->value('id');
        if ($fallback) {
            return (int) $fallback;
        }

        return (int) DB::table('milestones')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Milestone utama',
            'phase' => 'umum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function kanbanColumnForStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_DOING => 'doing',
            self::STATUS_DONE, 'done' => 'done',
            default => 'todo',
        };
    }

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Mudah',
            'high' => 'Sulit',
            default => 'Sedang',
        };
    }
}
