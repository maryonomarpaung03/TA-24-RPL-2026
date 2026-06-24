<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProblemIdentificationService
{
  public const CATEGORIES = ['Teknik', 'Diskusi', 'Etika', 'Kebutuhan Proyek'];

  public const PRIORITIES = ['Rendah', 'Sedang', 'Tinggi'];

  public function assertStudentAccess(int $projectId, User $user): Project
  {
    $project = Project::query()->findOrFail($projectId);

    if ($user->role === 'lecturer') {
      throw ValidationException::withMessages([
        'project' => 'Gunakan halaman review dosen.',
      ]);
    }

    if (! ProjectAccess::userCanAccess($user, $project)) {
      abort(403, 'Anda tidak memiliki akses ke proyek ini.');
    }

    if (! ProjectAccess::canAccessPjbl($project->status)) {
      abort(403, 'Proyek belum disetujui dosen untuk tahap PjBL.');
    }

    return $project;
  }

  public function assertLecturerAccess(int $projectId, User $user): Project
  {
    $project = Project::query()->findOrFail($projectId);

    if (! ProjectAccess::lecturerCanView($user, $project)) {
      abort(403, 'Anda tidak memiliki akses ke proyek ini.');
    }

    return $project;
  }

  public function isPm(Project $project, int $userId): bool
  {
    return (int) $project->created_by === $userId;
  }

  public function participantCount(int $projectId): int
  {
    $memberIds = DB::table('project_members')
      ->where('project_id', $projectId)
      ->pluck('user_id');

    $creatorId = Project::query()->where('id', $projectId)->value('created_by');

    if ($creatorId) {
      $memberIds->push($creatorId);
    }

    return max(1, $memberIds->unique()->filter()->count());
  }

  /**
   * @return array{ide: array, voting: array, diajukan: array, perbaiki: array, selesai: array}
   */
  public function buildBoard(int $projectId, int $userId): array
  {
    $board = [
      'ide' => [],
      'voting' => [],
      'diajukan' => [],
      'perbaiki' => [],
      'selesai' => [],
    ];

    $myVoteProblemId = DB::table('problem_votes')
      ->where('project_id', $projectId)
      ->where('user_id', $userId)
      ->value('problem_id');

    $problems = DB::table('problem_identifications')
      ->leftJoin('users', 'users.id', '=', 'problem_identifications.created_by')
      ->where('problem_identifications.project_id', $projectId)
      ->orderByDesc('problem_identifications.created_at')
      ->select(
        'problem_identifications.*',
        'users.full_name as author_name',
        'users.name as author_short_name'
      )
      ->get();

    foreach ($problems as $problem) {
      $voteCount = DB::table('problem_votes')
        ->where('problem_id', $problem->id)
        ->count();

      $column = match ($problem->board_status) {
        'idea' => 'ide',
        'voting' => 'voting',
        'submitted' => 'diajukan',
        'revision' => 'perbaiki',
        'done' => 'selesai',
        default => 'ide',
      };

      $board[$column][] = [
        'id' => $problem->id,
        'title' => $problem->title,
        'description' => $problem->description ?? '',
        'category' => $problem->category,
        'priority' => $problem->priority,
        'attachment_link' => $problem->attachment_link,
        'votes' => $voteCount,
        'voting_open' => $problem->board_status === 'voting',
        'feedback' => $problem->lecturer_feedback,
        'created_by' => (int) $problem->created_by,
        'author_name' => $problem->author_name ?: $problem->author_short_name ?: 'Anggota',
        'status_label' => $problem->board_status === 'submitted'
          ? 'Menunggu Persetujuan Dosen'
          : null,
        'note' => $problem->lecturer_feedback,
        'date' => $problem->updated_at
          ? \Carbon\Carbon::parse($problem->updated_at)->format('d M Y')
          : null,
        'is_my_vote' => (int) $myVoteProblemId === (int) $problem->id,
      ];
    }

    return $board;
  }

  public function distinctVoterCount(int $projectId): int
  {
    return (int) DB::table('problem_votes')
      ->where('project_id', $projectId)
      ->distinct()
      ->count('user_id');
  }

  /**
   * @return array<int, array{name: string, initials: string, voted_at: string}>
   */
  public function votersForProblem(int $problemId): array
  {
    return DB::table('problem_votes')
      ->join('users', 'users.id', '=', 'problem_votes.user_id')
      ->where('problem_votes.problem_id', $problemId)
      ->orderBy('problem_votes.created_at')
      ->get([
        'users.full_name',
        'users.name',
        'problem_votes.created_at',
      ])
      ->map(function ($row) {
        $name = $row->full_name ?: $row->name ?: 'Anggota';
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(
          collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')
        ) ?: '?';

        return [
          'name' => $name,
          'initials' => $initials,
          'voted_at' => \Carbon\Carbon::parse($row->created_at)->diffForHumans(),
        ];
      })
      ->values()
      ->all();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function discussionThreadForProject(int $projectId): array
  {
    if (! Schema::hasColumn('discussions', 'problem_id')) {
      return [];
    }

    $rows = $this->discussionRowsQuery($projectId)
      ->orderBy('discussions.created_at')
      ->get();

    return $this->buildDiscussionTree($rows);
  }

  /**
   * @return array{comments: array<int, array<string, mixed>>}
   */
  public function addGeneralDiscussion(
    int $projectId,
    User $user,
    string $message,
    ?int $parentId = null
  ): array {
    $project = Project::query()->findOrFail($projectId);

    if ($user->role === 'lecturer') {
      throw ValidationException::withMessages([
        'role' => 'Hanya mahasiswa yang dapat berdiskusi di sini.',
      ]);
    }

    if (! ProjectAccess::userCanAccess($user, $project)) {
      abort(403, 'Anda tidak memiliki akses ke proyek ini.');
    }

    $message = trim($message);

    if ($message === '') {
      throw ValidationException::withMessages([
        'message' => 'Pesan tidak boleh kosong.',
      ]);
    }

    if (strlen($message) > 2000) {
      throw ValidationException::withMessages([
        'message' => 'Pesan maksimal 2000 karakter.',
      ]);
    }

    if ($parentId) {
      $parent = DB::table('discussions')
        ->where('id', $parentId)
        ->where('project_id', $projectId)
        ->whereNull('task_id')
        ->first();

      if (! $parent) {
        throw ValidationException::withMessages([
          'parent_id' => 'Komentar tidak ditemukan.',
        ]);
      }

      if (Schema::hasColumn('discussions', 'parent_id') && $parent->parent_id) {
        throw ValidationException::withMessages([
          'parent_id' => 'Balasan hanya dapat ditambahkan pada komentar utama.',
        ]);
      }
    }

    $insert = [
      'project_id' => $projectId,
      'user_id' => $user->id,
      'task_id' => null,
      'message' => $message,
      'created_at' => now(),
    ];

    if (Schema::hasColumn('discussions', 'problem_id')) {
      $insert['problem_id'] = null;
    }

    if (Schema::hasColumn('discussions', 'parent_id') && $parentId) {
      $insert['parent_id'] = $parentId;
    }

    DB::table('discussions')->insert($insert);

    return [
      'comments' => $this->discussionThreadForProject($projectId),
    ];
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function commentsForProblem(int $projectId, int $problemId): array
  {
    if (! Schema::hasColumn('discussions', 'problem_id')) {
      return [];
    }

    $rows = $this->discussionRowsQuery($projectId)
      ->where('discussions.problem_id', $problemId)
      ->orderBy('discussions.created_at')
      ->get();

    return $this->buildDiscussionTree($rows);
  }

  /**
   * @return array{comments: array<int, array<string, mixed>>}
   */
  public function addDiscussionComment(
    int $projectId,
    User $user,
    int $problemId,
    string $message,
    ?int $parentId = null
  ): array {
    $this->assertStudentAccess($projectId, $user);
    $problem = $this->findProblemInProject($projectId, $problemId);

    if ($problem->board_status !== 'voting') {
      throw ValidationException::withMessages([
        'problem' => 'Komentar hanya dapat ditambahkan pada masalah di tahap voting.',
      ]);
    }

    $message = trim($message);

    if ($message === '') {
      throw ValidationException::withMessages([
        'message' => 'Komentar tidak boleh kosong.',
      ]);
    }

    if (strlen($message) > 2000) {
      throw ValidationException::withMessages([
        'message' => 'Komentar maksimal 2000 karakter.',
      ]);
    }

    if ($parentId) {
      $parent = DB::table('discussions')
        ->where('id', $parentId)
        ->where('project_id', $projectId)
        ->whereNull('task_id')
        ->first();

      if (! $parent) {
        throw ValidationException::withMessages([
          'parent_id' => 'Komentar induk tidak ditemukan.',
        ]);
      }

      if (Schema::hasColumn('discussions', 'parent_id') && $parent->parent_id) {
        throw ValidationException::withMessages([
          'parent_id' => 'Balasan hanya dapat ditambahkan pada komentar utama.',
        ]);
      }

      if (
        Schema::hasColumn('discussions', 'problem_id')
        && (int) $parent->problem_id !== $problemId
      ) {
        throw ValidationException::withMessages([
          'parent_id' => 'Balasan harus pada komentar masalah yang sama.',
        ]);
      }
    }

    $insert = [
      'project_id' => $projectId,
      'user_id' => $user->id,
      'task_id' => null,
      'message' => $message,
      'created_at' => now(),
    ];

    if (Schema::hasColumn('discussions', 'problem_id')) {
      $insert['problem_id'] = $problemId;
    }

    if (Schema::hasColumn('discussions', 'parent_id') && $parentId) {
      $insert['parent_id'] = $parentId;
    }

    DB::table('discussions')->insert($insert);

    return [
      'comments' => $this->discussionThreadForProject($projectId),
    ];
  }

  /**
   * @return \Illuminate\Database\Query\Builder
   */
  private function discussionRowsQuery(int $projectId)
  {
    $query = DB::table('discussions')
      ->join('users', 'users.id', '=', 'discussions.user_id')
      ->leftJoin(
        'problem_identifications',
        'problem_identifications.id',
        '=',
        'discussions.problem_id'
      )
      ->where('discussions.project_id', $projectId)
      ->whereNull('discussions.task_id');

    $select = [
      'discussions.id',
      'discussions.message',
      'discussions.problem_id',
      'discussions.created_at',
      'users.full_name',
      'users.name',
      'problem_identifications.title as problem_title',
    ];

    if (Schema::hasColumn('discussions', 'parent_id')) {
      $select[] = 'discussions.parent_id';
    }

    return $query->select($select);
  }

  /**
   * @param  \Illuminate\Support\Collection<int, object>  $rows
   * @return array<int, array<string, mixed>>
   */
  private function buildDiscussionTree($rows): array
  {
    $items = [];

    foreach ($rows as $row) {
      $items[(int) $row->id] = [
        'id' => (int) $row->id,
        'from' => $row->full_name ?: $row->name ?: 'Anggota',
        'text' => $row->message,
        'time' => \Carbon\Carbon::parse($row->created_at)->diffForHumans(),
        'problem_id' => $row->problem_id ? (int) $row->problem_id : null,
        'problem_title' => $row->problem_title ?: null,
        'parent_id' => property_exists($row, 'parent_id') ? $row->parent_id : null,
        'replies' => [],
      ];
    }

    foreach ($items as $id => $item) {
      $parentId = $item['parent_id'] ? (int) $item['parent_id'] : null;

      if ($parentId && isset($items[$parentId])) {
        $items[$parentId]['replies'][] = $items[$id];
      }
    }

    return array_values(array_filter(
      $items,
      fn (array $item) => empty($item['parent_id'])
    ));
  }

  public function topVotingProblemId(int $projectId): ?int
  {
    $row = DB::table('problem_identifications')
      ->leftJoin('problem_votes', 'problem_votes.problem_id', '=', 'problem_identifications.id')
      ->where('problem_identifications.project_id', $projectId)
      ->where('problem_identifications.board_status', 'voting')
      ->groupBy('problem_identifications.id')
      ->orderByRaw('COUNT(problem_votes.id) DESC')
      ->orderBy('problem_identifications.created_at')
      ->select('problem_identifications.id')
      ->first();

    return $row ? (int) $row->id : null;
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function createIdea(int $projectId, User $user, array $data): array
  {
    $this->assertStudentAccess($projectId, $user);

    $id = DB::table('problem_identifications')->insertGetId([
      'project_id' => $projectId,
      'created_by' => $user->id,
      'title' => $data['title'],
      'description' => $data['description'] ?? null,
      'category' => $data['category'],
      'priority' => $data['priority'],
      'attachment_link' => $data['attachment'] ?? null,
      'board_status' => 'idea',
      'voting_open' => false,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return $this->findCard($id, $user->id);
  }

  public function proposeForVoting(int $projectId, int $problemId, User $user): array
  {
    $this->assertStudentAccess($projectId, $user);
    $problem = $this->findProblemInProject($projectId, $problemId);

    if ($problem->board_status !== 'idea') {
      throw ValidationException::withMessages([
        'problem' => 'Hanya ide masalah yang dapat diajukan ke voting.',
      ]);
    }

    DB::table('problem_identifications')
      ->where('id', $problemId)
      ->update([
        'board_status' => 'voting',
        'voting_open' => true,
        'updated_at' => now(),
      ]);

    return $this->findCard($problemId, $user->id);
  }

  public function castVote(int $projectId, int $problemId, User $user): array
  {
    $this->assertStudentAccess($projectId, $user);
    $problem = $this->findProblemInProject($projectId, $problemId);

    if ($problem->board_status !== 'voting') {
      throw ValidationException::withMessages([
        'problem' => 'Masalah ini tidak sedang dalam tahap voting.',
      ]);
    }

    $existing = DB::table('problem_votes')
      ->where('project_id', $projectId)
      ->where('user_id', $user->id)
      ->first();

    if ($existing && (int) $existing->problem_id === $problemId) {
      DB::table('problem_votes')->where('id', $existing->id)->delete();
    } elseif ($existing) {
      DB::table('problem_votes')
        ->where('id', $existing->id)
        ->update([
          'problem_id' => $problemId,
          'updated_at' => now(),
        ]);
    } else {
      DB::table('problem_votes')->insert([
        'problem_id' => $problemId,
        'project_id' => $projectId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    $board = $this->buildBoard($projectId, $user->id);
    $autoSubmitted = $this->maybeAutoSubmitToLecturer($projectId, $user);

    return [
      'board' => $board,
      'auto_submitted' => $autoSubmitted,
    ];
  }

  public function submitToLecturer(int $projectId, User $user, bool $forcePm = true): array
  {
    $project = $this->assertStudentAccess($projectId, $user);

    if ($forcePm && ! $this->isPm($project, $user->id)) {
      throw ValidationException::withMessages([
        'role' => 'Hanya Project Manager yang dapat mengajukan ke dosen.',
      ]);
    }

    $winnerId = $this->topVotingProblemId($projectId);

    if (! $winnerId) {
      throw ValidationException::withMessages([
        'voting' => 'Belum ada masalah di tahap voting.',
      ]);
    }

    return $this->moveWinnerToSubmitted($project, $winnerId);
  }

  public function maybeAutoSubmitToLecturer(int $projectId, User $user): bool
  {
    $project = Project::query()->find($projectId);

    if (! $project) {
      return false;
    }

    $participants = $this->participantCount($projectId);
    $voters = $this->distinctVoterCount($projectId);

    if ($voters < $participants) {
      return false;
    }

    if (DB::table('problem_identifications')
      ->where('project_id', $projectId)
      ->where('board_status', 'submitted')
      ->exists()
    ) {
      return false;
    }

    $winnerId = $this->topVotingProblemId($projectId);

    if (! $winnerId) {
      return false;
    }

    $this->moveWinnerToSubmitted($project, $winnerId);

    return true;
  }

  /**
   * @return array<string, mixed>
   */
  private function moveWinnerToSubmitted(Project $project, int $winnerId): array
  {
    DB::table('problem_identifications')
      ->where('project_id', $project->id)
      ->where('board_status', 'voting')
      ->where('id', '!=', $winnerId)
      ->update([
        'board_status' => 'idea',
        'voting_open' => false,
        'updated_at' => now(),
      ]);

    DB::table('problem_identifications')
      ->where('id', $winnerId)
      ->update([
        'board_status' => 'submitted',
        'voting_open' => false,
        'updated_at' => now(),
      ]);

    $this->notifyLecturer($project, $winnerId);

    return $this->findCard($winnerId, (int) auth()->id());
  }

  public function resubmit(int $projectId, int $problemId, User $user, array $data): array
  {
    $this->assertStudentAccess($projectId, $user);
    $problem = $this->findProblemInProject($projectId, $problemId);

    if ($problem->board_status !== 'revision') {
      throw ValidationException::withMessages([
        'problem' => 'Hanya masalah di kolom Perbaiki yang dapat diajukan ulang.',
      ]);
    }

    if (! $this->isPm(Project::query()->findOrFail($projectId), $user->id)) {
      throw ValidationException::withMessages([
        'role' => 'Hanya Project Manager yang dapat mengajukan ulang ke dosen.',
      ]);
    }

    DB::table('problem_identifications')
      ->where('id', $problemId)
      ->update([
        'title' => $data['title'] ?? $problem->title,
        'description' => $data['description'] ?? $problem->description,
        'category' => $data['category'] ?? $problem->category,
        'priority' => $data['priority'] ?? $problem->priority,
        'attachment_link' => $data['attachment'] ?? $problem->attachment_link,
        'board_status' => 'submitted',
        'lecturer_feedback' => null,
        'updated_at' => now(),
      ]);

    $project = Project::query()->findOrFail($projectId);
    $this->notifyLecturer($project, $problemId, true);

    return $this->findCard($problemId, $user->id);
  }

  public function lecturerReview(
    int $projectId,
    int $problemId,
    User $lecturer,
    string $action,
    ?string $feedback
  ): array {
    $project = $this->assertLecturerAccess($projectId, $lecturer);
    $problem = $this->findProblemInProject($projectId, $problemId);

    if ($problem->board_status !== 'submitted') {
      throw ValidationException::withMessages([
        'problem' => 'Masalah ini tidak menunggu review dosen.',
      ]);
    }

    if ($action === 'reject' && empty(trim((string) $feedback))) {
      throw ValidationException::withMessages([
        'feedback' => 'Umpan balik wajib diisi saat menolak.',
      ]);
    }

    $newStatus = $action === 'approve' ? 'done' : 'revision';

    DB::table('problem_identifications')
      ->where('id', $problemId)
      ->update([
        'board_status' => $newStatus,
        'lecturer_feedback' => $feedback,
        'updated_at' => now(),
      ]);

    $this->notifyStudents($project, $problemId, $action, $feedback);

    return $this->findCard($problemId, (int) $problem->created_by);
  }

  private function notifyLecturer(Project $project, int $problemId, bool $resubmit = false): void
  {
    $email = strtolower(trim((string) $project->lecturer_email));

    if ($email === '') {
      return;
    }

    $problem = DB::table('problem_identifications')->where('id', $problemId)->first();

    DB::table('project_notifications')->insert([
      'project_id' => $project->id,
      'recipient_email' => $email,
      'type' => $resubmit ? 'problem_resubmitted' : 'problem_submitted_for_review',
      'title' => $resubmit ? 'Masalah utama diajukan ulang' : 'Masalah utama menunggu review',
      'message' => 'Masalah "' . ($problem->title ?? 'Utama') . '" dari proyek "' . $project->title . '" perlu ditinjau.',
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  private function notifyStudents(
    Project $project,
    int $problemId,
    string $action,
    ?string $feedback
  ): void {
    $problem = DB::table('problem_identifications')->where('id', $problemId)->first();
    $emails = $this->teamEmails($project->id, (int) $project->created_by);

    $approved = $action === 'approve';
    $title = $approved ? 'Masalah utama disetujui' : 'Masalah perlu diperbaiki';
    $message = $approved
      ? 'Dosen menyetujui masalah "' . ($problem->title ?? '') . '".'
      : 'Dosen meminta perbaikan: ' . ($feedback ?? '');

    foreach ($emails as $email) {
      DB::table('project_notifications')->insert([
        'project_id' => $project->id,
        'recipient_email' => $email,
        'type' => $approved ? 'problem_approved' : 'problem_revision',
        'title' => $title,
        'message' => $message,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  /**
   * @return list<string>
   */
  private function teamEmails(int $projectId, int $creatorId): array
  {
    $memberEmails = DB::table('project_members')
      ->join('users', 'users.id', '=', 'project_members.user_id')
      ->where('project_members.project_id', $projectId)
      ->pluck('users.email')
      ->map(fn ($e) => strtolower(trim((string) $e)))
      ->all();

    $creatorEmail = DB::table('users')->where('id', $creatorId)->value('email');

    if ($creatorEmail) {
      $memberEmails[] = strtolower(trim((string) $creatorEmail));
    }

    return array_values(array_unique(array_filter($memberEmails)));
  }

  private function findProblemInProject(int $projectId, int $problemId): object
  {
    $problem = DB::table('problem_identifications')
      ->where('id', $problemId)
      ->where('project_id', $projectId)
      ->first();

    if (! $problem) {
      abort(404, 'Masalah tidak ditemukan.');
    }

    return $problem;
  }

  /**
   * @return array<string, mixed>
   */
  private function findCard(int $problemId, int $userId): array
  {
    $problem = DB::table('problem_identifications')
      ->leftJoin('users', 'users.id', '=', 'problem_identifications.created_by')
      ->where('problem_identifications.id', $problemId)
      ->select(
        'problem_identifications.*',
        'users.full_name as author_name',
        'users.name as author_short_name'
      )
      ->first();

    $votes = DB::table('problem_votes')->where('problem_id', $problemId)->count();
    $myVote = DB::table('problem_votes')
      ->where('problem_id', $problemId)
      ->where('user_id', $userId)
      ->exists();

    return [
      'id' => $problem->id,
      'title' => $problem->title,
      'description' => $problem->description ?? '',
      'category' => $problem->category,
      'priority' => $problem->priority,
      'attachment_link' => $problem->attachment_link,
      'votes' => $votes,
      'created_by' => (int) $problem->created_by,
      'author_name' => $problem->author_name ?: $problem->author_short_name ?: 'Anggota',
      'feedback' => $problem->lecturer_feedback,
      'note' => $problem->lecturer_feedback,
      'status_label' => $problem->board_status === 'submitted'
        ? 'Menunggu Persetujuan Dosen'
        : null,
      'is_my_vote' => $myVote,
    ];
  }
}
