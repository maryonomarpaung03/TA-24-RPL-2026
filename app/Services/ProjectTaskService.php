<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectTaskService
{
    public const STATUS_TODO = 'pending';

    public const STATUS_DOING = 'in_progress';

    public const STATUS_DONE = 'completed';

    /**
     * Kolom default yang dibuat otomatis. Key sengaja dibuat sama dengan
     * status lama agar tugas eksisting langsung terpetakan.
     *
     * @var list<array{key: string, label: string, color: string}>
     */
    private const DEFAULT_COLUMNS = [
        ['key' => self::STATUS_TODO, 'label' => 'Belum Dikerjakan', 'color' => 'blue-600'],
        ['key' => self::STATUS_DOING, 'label' => 'Sedang Dikerjakan', 'color' => 'yellow-400'],
        ['key' => self::STATUS_DONE, 'label' => 'Selesai', 'color' => 'green-500'],
    ];

    /** @deprecated Kolom tidak lagi dapat dikonfigurasi dari papan. */
    public const COLUMN_COLORS = [
        'blue-600', 'yellow-400', 'green-500', 'red-500', 'purple-500',
        'pink-500', 'indigo-500', 'orange-500', 'teal-500', 'slate-500',
    ];

    /**
     * Pastikan papan selalu memakai tiga kolom status tetap.
     *
     * Versi awal papan mengizinkan kolom tambahan dengan checklist dan approval.
     * Konfigurasi tersebut dapat menahan perpindahan tugas. Kolom tambahan kini
     * dipensiunkan; tugasnya dikembalikan ke "Belum Dikerjakan".
     */
    public function ensureColumns(int $projectId): void
    {
        DB::transaction(function () use ($projectId): void {
            $fixedKeys = array_column(self::DEFAULT_COLUMNS, 'key');

            // Tugas yang sebelumnya berada di kolom kustom tetap ada, tetapi
            // kembali ke status awal karena kolom kustom tidak lagi tersedia.
            DB::table('tasks')
                ->where('project_id', $projectId)
                ->whereNotIn('status', $fixedKeys)
                ->update([
                    'status' => self::STATUS_TODO,
                    'progress_percent' => 0,
                    'updated_at' => now(),
                ]);

            // Fitur approval kolom dipensiunkan; pengajuan lama tidak boleh
            // lagi menghalangi perpindahan tugas.
            DB::table('task_approvals')
                ->where('project_id', $projectId)
                ->where('status', 'pending')
                ->delete();

            DB::table('project_task_columns')
                ->where('project_id', $projectId)
                ->whereNotIn('key', $fixedKeys)
                ->delete();

            foreach (self::DEFAULT_COLUMNS as $position => $col) {
                $attributes = [
                    'label' => $col['label'],
                    'color' => $col['color'],
                    'description' => null,
                    'is_done_column' => $col['key'] === self::STATUS_DONE,
                    'requires_approval' => false,
                    'checklist' => null,
                    'position' => $position,
                    'updated_at' => now(),
                ];

                $existing = DB::table('project_task_columns')
                    ->where('project_id', $projectId)
                    ->where('key', $col['key'])
                    ->first();

                if ($existing) {
                    DB::table('project_task_columns')->where('id', $existing->id)->update($attributes);
                    continue;
                }

                DB::table('project_task_columns')->insert($attributes + [
                    'project_id' => $projectId,
                    'key' => $col['key'],
                    'created_at' => now(),
                ]);
            }
        });
    }

    /**
     * @return list<array{id: int, key: string, label: string, color: string, description: ?string, is_done: bool, requires_approval: bool, checklist: list<string>}>
     */
    public function columnsForProject(int $projectId): array
    {
        $this->ensureColumns($projectId);

        return DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($c) => $this->mapColumn($c))
            ->all();
    }

    /**
     * @return array{id: int, key: string, label: string, color: string, description: ?string, is_done: bool, requires_approval: bool, checklist: list<string>}
     */
    private function mapColumn(object $c): array
    {
        $checklist = [];
        if (! empty($c->checklist)) {
            $decoded = json_decode((string) $c->checklist, true);
            if (is_array($decoded)) {
                $checklist = array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return [
            'id' => (int) $c->id,
            'key' => $c->key,
            'label' => $c->label,
            'color' => $c->color,
            'description' => $c->description ?? null,
            'is_done' => (bool) ($c->is_done_column ?? false),
            'requires_approval' => (bool) ($c->requires_approval ?? false),
            'checklist' => $checklist,
        ];
    }

    /**
     * Papan kanban dinamis: daftar kolom, tiap kolom berisi tugasnya.
     *
     * @return list<array{id: int, key: string, label: string, color: string, tasks: list<array<string, mixed>>}>
     */
    public function kanbanForProject(int $projectId): array
    {
        $columns = $this->columnsForProject($projectId);
        $firstKey = $columns[0]['key'] ?? self::STATUS_TODO;
        $validKeys = array_column($columns, 'key');

        $board = [];
        foreach ($columns as $col) {
            $board[$col['key']] = $col + ['tasks' => []];
        }

        $commentsMap = $this->commentsByTask($projectId);
        $columnLabels = array_column($columns, 'label', 'key');

        // Pending approval per tugas (hold pattern): tugas menunggu pindah ke kolom tujuan.
        $pendingMap = DB::table('task_approvals')
            ->where('project_id', $projectId)
            ->where('status', 'pending')
            ->get()
            ->keyBy('task_id');

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
                'tasks.assigned_to',
                'tasks.link',
                'tasks.submission_type',
                'tasks.attachment_path',
                'tasks.attachment_name',
                'tasks.attachment_mime',
                'tasks.reviewed_at',
                'users.full_name',
                'users.name as user_name'
            )
            ->get();

        foreach ($rows as $row) {
            $status = (string) $row->status;
            // Status tak dikenal (mis. sisa data lama) jatuh ke kolom pertama.
            $key = in_array($status, $validKeys, true) ? $status : $firstKey;
            $assigneeName = $row->full_name ?: $row->user_name ?: 'Tim';
            $urgency = self::urgencyMeta($row->due_date);

            $pending = $pendingMap->get($row->id);

            $board[$key]['tasks'][] = [
                'id' => (int) $row->id,
                'name' => $row->task_title,
                'description' => $row->description ?? '',
                'creator' => ProjectAccess::initialsFromName($assigneeName),
                'assignee' => $assigneeName,
                'assigned_to' => (int) $row->assigned_to,
                'level' => $this->priorityLabel((string) $row->priority),
                'due_date' => $row->due_date,
                'days_left' => $urgency['days_left'],
                'urgency' => $urgency['urgency'],
                'urgency_label' => $urgency['label'],
                'comments' => $commentsMap[$row->id] ?? [],
                'pending_to' => $pending
                    ? ($columnLabels[$pending->to_column_key] ?? $pending->to_column_key)
                    : null,
                'submission' => self::submissionMeta($row),
                'reviewed_at' => $row->reviewed_at
                    ? \Carbon\Carbon::parse($row->reviewed_at)->format('d M Y H:i')
                    : null,
            ];
        }

        return array_values($board);
    }

    /**
     * Bukti pengumpulan tugas: berkas unggahan atau tautan. Null bila mahasiswa
     * belum mengumpulkan apa pun.
     *
     * @return array{kind: string, label: string, url: string, is_image: bool}|null
     */
    public static function submissionMeta(object $row): ?array
    {
        if (! empty($row->attachment_path)) {
            return [
                'kind' => 'file',
                'label' => $row->attachment_name ?: 'Berkas tugas',
                // asset() mengikuti host permintaan, sama seperti papan mahasiswa.
                // Storage::url() akan terkunci ke APP_URL dan putus di host lain.
                'url' => asset('storage/'.$row->attachment_path),
                'is_image' => str_starts_with((string) $row->attachment_mime, 'image/'),
            ];
        }

        if (! empty($row->link)) {
            return [
                'kind' => 'link',
                'label' => $row->link,
                'url' => $row->link,
                'is_image' => false,
            ];
        }

        return null;
    }

    /**
     * Tandai tugas sudah direview dosen. Idempoten: memanggil ulang hanya
     * memperbarui waktu review.
     */
    public function markReviewed(int $projectId, int $taskId, int $lecturerId): void
    {
        $task = DB::table('tasks')
            ->where('id', $taskId)
            ->where('project_id', $projectId)
            ->first();

        if (! $task) {
            throw ValidationException::withMessages(['task' => 'Tugas tidak ditemukan.']);
        }

        DB::table('tasks')
            ->where('id', $taskId)
            ->update([
                'reviewed_at' => now(),
                'reviewed_by' => $lecturerId,
                'updated_at' => now(),
            ]);

        if (! $task->assigned_to) {
            return;
        }

        $email = DB::table('users')->where('id', $task->assigned_to)->value('email');

        if (! $email) {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $projectId,
            'recipient_email' => strtolower(trim((string) $email)),
            'type' => 'task_reviewed',
            'title' => 'Tugas Anda sudah direview dosen',
            'message' => 'Dosen telah mereview tugas "'.$task->task_title.'". Cek komentar pada tugas tersebut.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Komentar tiap tugas (dari tabel discussions), dikelompokkan per task_id.
     *
     * @return array<int, list<array{from: string, text: string, time: string}>>
     */
    private function commentsByTask(int $projectId): array
    {
        $rows = DB::table('discussions')
            ->leftJoin('users', 'users.id', '=', 'discussions.user_id')
            ->where('discussions.project_id', $projectId)
            ->whereNotNull('discussions.task_id')
            ->where('discussions.context', 'execution')
            ->orderBy('discussions.created_at')
            ->select(
                'discussions.task_id',
                'discussions.message',
                'discussions.created_at',
                'users.full_name',
                'users.name',
                'users.role'
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $name = $row->full_name ?: $row->name ?: 'Anggota';
            $map[(int) $row->task_id][] = [
                'from' => $name.($row->role === 'lecturer' ? ' (Dosen)' : ''),
                'text' => $row->message,
                'time' => \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i'),
            ];
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $config  description, is_done, requires_approval, checklist[]
     * @return array{id: int}
     */
    public function createColumn(int $projectId, string $label, string $color, array $config = []): array
    {
        $this->ensureColumns($projectId);

        $label = trim($label);
        if ($label === '') {
            throw ValidationException::withMessages(['label' => 'Nama kolom tidak boleh kosong.']);
        }

        $position = (int) DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->max('position');

        $id = (int) DB::table('project_task_columns')->insertGetId([
            'project_id' => $projectId,
            'key' => 'tmp',
            'label' => $label,
            'color' => $this->normalizeColor($color),
            'position' => $position + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ] + $this->columnConfigAttributes($config));

        DB::table('project_task_columns')
            ->where('id', $id)
            ->update(['key' => 'col_'.$id]);

        return ['id' => $id];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function updateColumn(int $projectId, int $columnId, string $label, string $color, array $config = []): void
    {
        $label = trim($label);
        if ($label === '') {
            throw ValidationException::withMessages(['label' => 'Nama kolom tidak boleh kosong.']);
        }

        $updated = DB::table('project_task_columns')
            ->where('id', $columnId)
            ->where('project_id', $projectId)
            ->update([
                'label' => $label,
                'color' => $this->normalizeColor($color),
                'updated_at' => now(),
            ] + $this->columnConfigAttributes($config));

        if (! $updated) {
            throw ValidationException::withMessages(['column' => 'Kolom tidak ditemukan.']);
        }
    }

    /**
     * Normalisasi atribut konfigurasi kolom untuk insert/update.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function columnConfigAttributes(array $config): array
    {
        $description = isset($config['description']) ? trim((string) $config['description']) : '';

        $checklist = [];
        if (! empty($config['checklist']) && is_array($config['checklist'])) {
            $checklist = array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $config['checklist']
            ), fn ($item) => $item !== ''));
            $checklist = array_slice($checklist, 0, 15);
        }

        return [
            'description' => $description !== '' ? $description : null,
            'is_done_column' => ! empty($config['is_done']),
            'requires_approval' => ! empty($config['requires_approval']),
            'checklist' => $checklist === [] ? null : json_encode(array_values($checklist)),
        ];
    }

    public function deleteColumn(int $projectId, int $columnId): void
    {
        $columns = DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($columns->count() <= 1) {
            throw ValidationException::withMessages([
                'column' => 'Minimal harus ada satu kolom pada papan.',
            ]);
        }

        $target = $columns->firstWhere('id', $columnId);
        if (! $target) {
            throw ValidationException::withMessages(['column' => 'Kolom tidak ditemukan.']);
        }

        $fallback = $columns->firstWhere('id', '!=', $columnId);

        $orphanIds = DB::table('tasks')
            ->where('project_id', $projectId)
            ->where('status', $target->key)
            ->pluck('id');

        // Pengajuan persetujuan menuju/berasal dari kolom ini tidak lagi bermakna.
        DB::table('task_approvals')
            ->where('project_id', $projectId)
            ->where('status', 'pending')
            ->where(function ($q) use ($target) {
                $q->where('to_column_key', $target->key)
                    ->orWhere('from_column_key', $target->key);
            })
            ->delete();

        DB::table('project_task_columns')
            ->where('id', $columnId)
            ->where('project_id', $projectId)
            ->delete();

        // Tugas di kolom ini dipindahkan ke kolom tersisa (tidak ikut dihapus).
        // Dilakukan setelah kolomnya hilang agar progres dihitung dari papan baru.
        foreach ($orphanIds as $taskId) {
            $this->applyColumn($projectId, (int) $taskId, (string) $fallback->key);
        }
    }

    /**
     * Pindahkan tugas antar kolom, dengan penegakan checklist (DoD) & approval Dosen.
     *
     * @return array{pending: bool}
     */
    public function moveTask(
        int $projectId,
        int $taskId,
        string $columnKey,
        int $userId,
        bool $checklistConfirmed = false
    ): array {
        $columnRow = DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->where('key', $columnKey)
            ->first();

        if (! $columnRow) {
            throw ValidationException::withMessages(['column' => 'Kolom tujuan tidak ditemukan.']);
        }

        $task = DB::table('tasks')
            ->where('id', $taskId)
            ->where('project_id', $projectId)
            ->first();

        if (! $task) {
            throw ValidationException::withMessages(['task' => 'Tugas tidak ditemukan.']);
        }

        // Tidak ada perubahan bila kolom tujuan sama dengan kolom saat ini.
        if ((string) $task->status === $columnKey) {
            return ['pending' => false];
        }

        // Blokir bila tugas masih menunggu persetujuan yang belum selesai.
        $hasPending = DB::table('task_approvals')
            ->where('task_id', $taskId)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'task' => 'Tugas ini masih menunggu persetujuan Dosen.',
            ]);
        }

        $column = $this->mapColumn($columnRow);

        // Checklist Definition of Done wajib dicentang semua sebelum pindah.
        if (! empty($column['checklist']) && ! $checklistConfirmed) {
            throw ValidationException::withMessages([
                'checklist' => 'Lengkapi dulu semua checklist "'.$column['label'].'" sebelum memindahkan tugas.',
            ]);
        }

        // Kolom ber-approval: tahan tugas, buat pengajuan persetujuan ke Dosen.
        if ($column['requires_approval']) {
            DB::table('task_approvals')->insert([
                'project_id' => $projectId,
                'task_id' => $taskId,
                'from_column_key' => $task->status,
                'to_column_key' => $columnKey,
                'status' => 'pending',
                'checklist_snapshot' => $column['checklist'] === [] ? null : json_encode($column['checklist']),
                'requested_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->notifyLecturerApproval($projectId, $task, $column);

            return ['pending' => true];
        }

        $this->applyColumn($projectId, $taskId, $columnKey);

        return ['pending' => false];
    }

    /**
     * Pindahkan tugas ke sebuah kolom. Progres ikut disetel dari posisi kolom,
     * karena tugas tidak lagi punya form progres manual di Pelaksanaan: kolom
     * pertama 0%, kolom "Selesai" 100%, kolom di antaranya 50%.
     */
    private function applyColumn(int $projectId, int $taskId, string $columnKey): void
    {
        $columns = $this->columnsForProject($projectId);
        $firstKey = $columns[0]['key'] ?? self::STATUS_TODO;

        $isDone = collect($columns)
            ->firstWhere('key', $columnKey)['is_done'] ?? false;

        $progress = match (true) {
            (bool) $isDone => 100,
            $columnKey === $firstKey => 0,
            default => 50,
        };

        DB::table('tasks')
            ->where('id', $taskId)
            ->where('project_id', $projectId)
            ->update([
                'status' => $columnKey,
                'progress_percent' => $progress,
                'updated_at' => now(),
            ]);
    }

    /**
     * Hapus tugas beserta jejaknya: komentar, pengajuan persetujuan, dan berkas
     * pengumpulan. Dipakai dari Penyusunan — satu-satunya tempat tugas dihapus.
     */
    public function deleteTask(int $projectId, int $taskId): bool
    {
        $task = DB::table('tasks')
            ->where('id', $taskId)
            ->where('project_id', $projectId)
            ->first();

        if (! $task) {
            return false;
        }

        if (! empty($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        DB::table('discussions')->where('task_id', $taskId)->delete();
        DB::table('task_approvals')->where('task_id', $taskId)->delete();
        DB::table('tasks')->where('id', $taskId)->delete();

        return true;
    }

    /**
     * Rekap kontribusi tiap anggota proyek.
     *
     * @return list<array<string, mixed>>
     */
    public function contributionForProject(int $projectId): array
    {
        $members = $this->assignableMembers($projectId);
        $doneKeys = $this->doneColumnKeys($projectId);

        $taskCounts = DB::table('tasks')
            ->where('project_id', $projectId)
            ->select('assigned_to', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_to', 'status')
            ->get();

        $commentCounts = DB::table('discussions')
            ->join('tasks', 'tasks.id', '=', 'discussions.task_id')
            ->where('tasks.project_id', $projectId)
            ->whereNotNull('discussions.task_id')
            ->select('discussions.user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('discussions.user_id')
            ->pluck('total', 'discussions.user_id');

        return array_map(function ($member) use ($taskCounts, $commentCounts, $doneKeys) {
            $rows = $taskCounts->where('assigned_to', $member->id);
            $done = (int) $rows->whereIn('status', $doneKeys)->sum('total');
            $doing = (int) $rows->where('status', self::STATUS_DOING)->sum('total');
            $assigned = (int) $rows->sum('total');

            return [
                'id' => $member->id,
                'name' => $member->full_name,
                'initials' => ProjectAccess::initialsFromName($member->full_name),
                'assigned' => $assigned,
                'done' => $done,
                'doing' => $doing,
                'todo' => max(0, $assigned - $done - $doing),
                'comments' => (int) ($commentCounts[$member->id] ?? 0),
            ];
        }, $members);
    }

    /**
     * Ringkasan progres keseluruhan proyek.
     *
     * @return array{total: int, done: int, percent: int, overdue: int}
     */
    public function progressForProject(int $projectId): array
    {
        $doneKeys = $this->doneColumnKeys($projectId);

        $total = (int) DB::table('tasks')->where('project_id', $projectId)->count();
        $done = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereIn('status', $doneKeys)
            ->count();

        $overdue = (int) DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereNotIn('status', $doneKeys)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'overdue' => $overdue,
        ];
    }

    /**
     * Key kolom "Selesai" milik proyek, untuk dipakai controller lain.
     *
     * @return list<string>
     */
    public function doneKeysForProject(int $projectId): array
    {
        return $this->doneColumnKeys($projectId);
    }

    /**
     * Status pemantauan sebuah tugas: selesai, terlewat (lewat tenggat & belum
     * selesai), sedang dikerjakan, atau belum dikerjakan.
     *
     * @param  list<string>  $doneKeys
     * @return array{key: string, label: string, tone: string}
     */
    public static function taskStatusMeta(?string $status, ?string $dueDate, array $doneKeys): array
    {
        if ($status !== null && in_array($status, $doneKeys, true)) {
            return ['key' => 'selesai', 'label' => 'Selesai', 'tone' => 'emerald'];
        }

        if ($dueDate && \Carbon\Carbon::parse($dueDate)->endOfDay()->isPast()) {
            return ['key' => 'terlewat', 'label' => 'Terlewat', 'tone' => 'red'];
        }

        if ($status === self::STATUS_DOING) {
            return ['key' => 'dikerjakan', 'label' => 'Sedang Dikerjakan', 'tone' => 'amber'];
        }

        return ['key' => 'belum', 'label' => 'Belum Dikerjakan', 'tone' => 'slate'];
    }

    /**
     * Key kolom yang ditandai sebagai "Selesai". Selalu berisi minimal 1 elemen
     * agar whereIn/whereNotIn tidak kosong.
     *
     * @return list<string>
     */
    private function doneColumnKeys(int $projectId): array
    {
        $keys = DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->where('is_done_column', true)
            ->pluck('key')
            ->all();

        return $keys === [] ? ['__none__'] : $keys;
    }

    /**
     * Daftar tugas yang menunggu persetujuan Dosen.
     *
     * @return list<array<string, mixed>>
     */
    public function pendingApprovalsForProject(int $projectId): array
    {
        $labels = DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->pluck('label', 'key');

        return DB::table('task_approvals')
            ->join('tasks', 'tasks.id', '=', 'task_approvals.task_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'task_approvals.requested_by')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'tasks.assigned_to')
            ->where('task_approvals.project_id', $projectId)
            ->where('task_approvals.status', 'pending')
            ->orderBy('task_approvals.created_at')
            ->select(
                'task_approvals.*',
                'tasks.task_title',
                'requester.full_name as requester_name',
                'requester.name as requester_short',
                'assignee.full_name as assignee_name',
                'assignee.name as assignee_short'
            )
            ->get()
            ->map(function ($r) use ($labels) {
                $checklist = json_decode((string) $r->checklist_snapshot, true);
                $checklist = is_array($checklist) ? array_values(array_map('strval', $checklist)) : [];

                return [
                    'id' => (int) $r->id,
                    'task_id' => (int) $r->task_id,
                    'task_title' => $r->task_title,
                    'from_label' => $labels[$r->from_column_key] ?? $r->from_column_key,
                    'to_label' => $labels[$r->to_column_key] ?? $r->to_column_key,
                    'requester' => $r->requester_name ?: $r->requester_short ?: 'Anggota',
                    'assignee' => $r->assignee_name ?: $r->assignee_short ?: 'Tim',
                    'checklist' => $checklist,
                    'requested_at' => \Carbon\Carbon::parse($r->created_at)->diffForHumans(),
                ];
            })
            ->all();
    }

    public function pendingApprovalCount(int $projectId): int
    {
        return (int) DB::table('task_approvals')
            ->where('project_id', $projectId)
            ->where('status', 'pending')
            ->count();
    }

    public function approveTask(int $projectId, int $approvalId, User $lecturer): void
    {
        $approval = $this->findPendingApproval($projectId, $approvalId);

        $this->applyColumn($projectId, (int) $approval->task_id, (string) $approval->to_column_key);

        DB::table('task_approvals')
            ->where('id', $approvalId)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $lecturer->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $this->notifyStudentApprovalResult($projectId, $approval, true, null);
    }

    public function rejectTask(int $projectId, int $approvalId, User $lecturer, ?string $note): void
    {
        $approval = $this->findPendingApproval($projectId, $approvalId);

        // Tugas tetap di kolom asal; hanya catat penolakan + catatan Dosen.
        DB::table('task_approvals')
            ->where('id', $approvalId)
            ->update([
                'status' => 'rejected',
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'reviewed_by' => $lecturer->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $this->notifyStudentApprovalResult($projectId, $approval, false, $note);
    }

    private function findPendingApproval(int $projectId, int $approvalId): object
    {
        $approval = DB::table('task_approvals')
            ->where('id', $approvalId)
            ->where('project_id', $projectId)
            ->where('status', 'pending')
            ->first();

        if (! $approval) {
            throw ValidationException::withMessages([
                'approval' => 'Pengajuan tidak ditemukan atau sudah diproses.',
            ]);
        }

        return $approval;
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function notifyLecturerApproval(int $projectId, object $task, array $column): void
    {
        $project = Project::query()->find($projectId);
        if (! $project) {
            return;
        }

        $email = strtolower(trim((string) $project->lecturer_email));
        if ($email === '') {
            return;
        }

        DB::table('project_notifications')->insert([
            'project_id' => $projectId,
            'recipient_email' => $email,
            'type' => 'task_approval_requested',
            'title' => 'Persetujuan tugas diminta',
            'message' => 'Tugas "'.$task->task_title.'" menunggu persetujuan Anda untuk masuk kolom "'.$column['label'].'".',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function notifyStudentApprovalResult(int $projectId, object $approval, bool $approved, ?string $note): void
    {
        $email = DB::table('users')->where('id', $approval->requested_by)->value('email');
        if (! $email) {
            return;
        }

        $taskTitle = DB::table('tasks')->where('id', $approval->task_id)->value('task_title');

        DB::table('project_notifications')->insert([
            'project_id' => $projectId,
            'recipient_email' => strtolower(trim((string) $email)),
            'type' => $approved ? 'task_approved' : 'task_rejected',
            'title' => $approved ? 'Tugas disetujui Dosen' : 'Tugas ditolak Dosen',
            'message' => $approved
                ? 'Tugas "'.$taskTitle.'" disetujui dan dipindahkan.'
                : 'Tugas "'.$taskTitle.'" ditolak Dosen.'.($note && trim($note) !== '' ? ' Catatan: '.trim($note) : ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeColor(string $color): string
    {
        $color = trim($color);

        return in_array($color, self::COLUMN_COLORS, true) ? $color : 'slate-500';
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
        int $createdByUserId,
        string $priority = 'medium'
    ): array {
        $milestoneId = $this->ensureMilestoneId((int) $project->id);

        $taskId = (int) DB::table('tasks')->insertGetId([
            'project_id' => $project->id,
            'milestone_id' => $milestoneId,
            'parent_task_id' => null,
            'assigned_to' => $assigneeId,
            'task_title' => $title,
            'description' => $description,
            'priority' => $priority,
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

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Mudah',
            'high' => 'Sulit',
            default => 'Sedang',
        };
    }

    /**
     * Metadata urgensi berdasarkan tanggal deadline.
     *
     * @return array{days_left: ?int, urgency: string, label: ?string}
     */
    public static function urgencyMeta(?string $dueDate): array
    {
        if (! $dueDate) {
            return ['days_left' => null, 'urgency' => 'none', 'label' => null];
        }

        $due = \Carbon\Carbon::parse($dueDate)->startOfDay();
        $days = (int) \Carbon\Carbon::today()->diffInDays($due, false);

        $urgency = match (true) {
            $days < 0 => 'overdue',
            $days === 0 => 'today',
            $days <= 3 => 'urgent',
            $days <= 7 => 'soon',
            default => 'normal',
        };

        $label = match ($urgency) {
            'overdue' => 'Terlewat',
            'today' => 'Jatuh Tempo Hari Ini',
            'urgent' => 'Urgent',
            'soon' => 'Mendekati',
            default => null,
        };

        return ['days_left' => $days, 'urgency' => $urgency, 'label' => $label];
    }
}
