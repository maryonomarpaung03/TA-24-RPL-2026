<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectTaskService;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenStudentProjectsController extends Controller
{
    public function __construct(
        private readonly ProjectTaskService $tasks,
        private readonly StageProgressService $stages,
    ) {}

    /**
     * Status DB => label UI. Kosakatanya mengikuti filter mahasiswa
     * (lihat ProjekSayaController), dibatasi ke status yang memang terlihat dosen.
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'active' => 'In Progress',
        'pending_final_review' => 'Menunggu Penilaian',
        'pending_final_revision' => 'Revisi Finalisasi',
        'completed' => 'Done',
    ];

    public function index(Request $request)
    {
        $this->ensureLecturer();

        $email = strtolower(trim((string) Auth::user()->email));

        $base = Project::query()
            ->where('lecturer_email', $email)
            ->whereIn('status', ProjectAccess::lecturerVisibleStatuses());

        $total = (clone $base)->count();

        $keyword = trim((string) $request->query('q', ''));
        $classId = (string) $request->query('kelas', '');
        $course = (string) $request->query('matkul', '');
        $status = (string) $request->query('status', '');
        $penilaian = (string) $request->query('penilaian', '');

        $query = clone $base;

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('group_name', 'LIKE', '%'.$keyword.'%');
            });
        }

        if ($classId !== '') {
            $query->where('academic_class_id', $classId);
        }

        if ($course !== '') {
            $query->where('course_name', $course);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($penilaian === 'sudah') {
            $query->whereIn('id', DB::table('project_evaluations')->select('project_id'));
        } elseif ($penilaian === 'belum') {
            $query->whereNotIn('id', DB::table('project_evaluations')->select('project_id'));
        }

        $projects = $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Project $p) => $this->mapListRow($p));

        $all = (clone $base)->get();

        return view('DosenStudentProjects', [
            'approved_projects' => $projects,
            'totalProjects' => $total,
            'filterState' => [
                'q' => $keyword,
                'kelas' => $classId,
                'matkul' => $course,
                'status' => $status,
                'penilaian' => $penilaian,
            ],
            'classOptions' => $this->classOptions($all),
            'courseOptions' => $all
                ->pluck('course_name')
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn ($c) => [$c => $c])
                ->all(),
            'statusOptions' => self::STATUS_LABELS,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Project>  $projects
     * @return array<int|string, string>
     */
    private function classOptions($projects): array
    {
        $ids = $projects->pluck('academic_class_id')->filter()->unique();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('academic_classes')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function show(int $id)
    {
        $this->ensureLecturer();

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        if (! in_array($project->status, ProjectAccess::lecturerVisibleStatuses(), true)) {
            return redirect()
                ->route('dosen.proyek-mahasiswa')
                ->with('error', 'Proyek ini belum disetujui atau masih menunggu persetujuan.');
        }

        return view('DosenStudentProjectDetail', [
            'project' => $this->mapDetailRow($project),
            'stage_overview' => $this->stages->overview((int) $project->id),
            'reopen_requests' => $this->stages->reopenRequests((int) $project->id),
        ]);
    }

    private function ensureLecturer(): void
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapListRow(Project $project): array
    {
        $creator = DB::table('users')
            ->where('id', $project->created_by)
            ->select('full_name', 'name', 'email')
            ->first();

        $memberCount = DB::table('project_members')
            ->where('project_id', $project->id)
            ->count();

        return [
            'id' => $project->id,
            'name' => $project->title,
            'status' => $project->status,
            'status_label' => ProjectAccess::toSelectedArray($project)['status_label'],
            'description' => ProjectAccess::displayDescription($project->description, 120),
            'group_name' => $project->group_name ?? '-',
            'course_name' => $project->course_name ?? '-',
            'creator_name' => $creator->full_name ?? $creator->name ?? '-',
            'member_count' => max(1, $memberCount + 1),
            'updated_at' => $project->updated_at?->format('d M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetailRow(Project $project): array
    {
        $creator = DB::table('users')
            ->where('id', $project->created_by)
            ->select('full_name', 'name', 'email')
            ->first();

        $parsed = ProjectAccess::parseProjectDescription($project->description);
        $attachmentUrl = ProjectAccess::projectAttachmentUrl(
            $project->logo,
            $project->description
        );

        $members = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $project->id)
            ->where('project_members.user_id', '!=', $project->created_by)
            ->select('users.full_name', 'users.name', 'users.email')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->full_name ?: $row->name,
                'email' => $row->email,
                'initials' => ProjectAccess::initialsFromName($row->full_name ?: $row->name),
            ])
            ->all();

        $pendingProblemReview = DB::table('problem_identifications')
            ->where('project_id', $project->id)
            ->where('board_status', 'submitted')
            ->exists();

        $progress = $this->tasks->progressForProject((int) $project->id);
        $pendingApproval = $this->tasks->pendingApprovalCount((int) $project->id);
        $isEvaluated = DB::table('project_evaluations')
            ->where('project_id', $project->id)
            ->exists();

        return [
            'id' => $project->id,
            'name' => $project->title,
            'status' => $project->status,
            'status_label' => ProjectAccess::toSelectedArray($project)['status_label'],
            'group_name' => $project->group_name,
            'course_name' => $project->course_name,
            'masalah' => $parsed['masalah'],
            'deskripsi' => $parsed['deskripsi'],
            'planned_months' => $project->planned_months,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'lecturer_name' => $project->lecturer_name,
            'lecturer_email' => $project->lecturer_email,
            'creator_name' => $creator->full_name ?? $creator->name ?? '-',
            'creator_email' => $creator->email ?? '-',
            'members' => $members,
            'attachment_url' => $attachmentUrl,
            'pending_problem_review' => $pendingProblemReview,
            'task_progress' => $progress,
            'pending_task_approval' => $pendingApproval,
            // Finalisasi kini dinyatakan tim lewat tombol Submit Finalisasi,
            // bukan ditebak dari jumlah tugas yang selesai.
            'tasks_finalized' => ProjectAccess::isFinalized($project->status),
            'is_evaluated' => $isEvaluated,
        ];
    }
}
