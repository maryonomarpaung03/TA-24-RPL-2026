<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjekSayaController extends Controller
{
    public function __construct(private readonly StageProgressService $stages) {}

    /** status DB => label UI */
    private const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_approval' => 'In Review',
        'pending_revision' => 'Review Perubahan',
        'active' => 'In Progress',
        'completed' => 'Done',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ];

    public function index(Request $request)
    {
        $currentUserId = (int) Auth::id();

        $projectIds = Project::query()
            ->where('created_by', $currentUserId)
            ->pluck('id')
            ->merge(
                DB::table('project_members')
                    ->where('user_id', $currentUserId)
                    ->pluck('project_id')
            )
            ->unique();

        $keyword = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $classId = (string) $request->query('kelas', '');
        $lecturer = (string) $request->query('dosen', '');
        $role = (string) $request->query('peran', '');

        $searchHistory = $this->rememberSearch($keyword);

        // Opsi filter diambil dari proyek milik user sendiri.
        $ownProjects = Project::query()->whereIn('id', $projectIds)->get();

        $query = Project::query()->whereIn('id', $projectIds);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('group_name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('course_name', 'LIKE', '%'.$keyword.'%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($classId !== '') {
            $query->where('academic_class_id', $classId);
        }

        if ($lecturer !== '') {
            $query->where('lecturer_email', $lecturer);
        }

        if ($role === 'pm') {
            $query->where('created_by', $currentUserId);
        } elseif ($role === 'anggota') {
            $query->where('created_by', '!=', $currentUserId);
        }

        $projects = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $project) => $this->mapCard($project, $currentUserId))
            ->all();

        return view('ProjekSaya', [
            'projects' => $projects,
            'searchHistory' => $searchHistory,
            'keyword' => $keyword,
            'totalProjects' => $ownProjects->count(),
            'filterState' => [
                'status' => $status,
                'kelas' => $classId,
                'dosen' => $lecturer,
                'peran' => $role,
            ],
            'statusOptions' => self::STATUS_LABELS,
            'classOptions' => $this->classOptions($ownProjects),
            'lecturerOptions' => $ownProjects
                ->filter(fn (Project $p) => $p->lecturer_email)
                ->mapWithKeys(fn (Project $p) => [$p->lecturer_email => $p->lecturer_name ?: $p->lecturer_email])
                ->sort()
                ->all(),
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

    /** @return list<string> */
    private function rememberSearch(string $keyword): array
    {
        $history = session('search_history', []);

        if ($keyword === '') {
            return $history;
        }

        array_unshift($history, $keyword);
        $history = array_values(array_slice(array_unique($history), 0, 5));
        session(['search_history' => $history]);

        return $history;
    }

    /** @return array<string, mixed> */
    private function mapCard(Project $project, int $currentUserId): array
    {
        $uiStatus = self::STATUS_LABELS[$project->status] ?? 'Planning';
        $members = ProjectAccess::memberInitials($project->id);

        return [
            'id' => $project->id,
            'name' => $project->title,
            'status' => $uiStatus,
            'label' => $uiStatus,
            'db_status' => $project->status,
            // Dihitung dari tahapan CT yang benar-benar diselesaikan, bukan ditebak
            // dari status proyek (dulu semua proyek "active" selalu tampil 60%).
            'progress' => $this->stages->percent((int) $project->id, $project->status),
            'progress_stage' => StageProgressService::label($this->stages->currentStage((int) $project->id)),
            'can_manage' => (int) $project->created_by === $currentUserId,
            'description' => ProjectAccess::shortDescription($project->description),
            'created_at' => Carbon::parse($project->created_at)->format('d/m/Y'),
            'member_count' => max(1, count($members)),
            'members' => $members,
            'lecturer_email' => $project->lecturer_email,
            'lecturer_name' => $project->lecturer_name,
            'group_name' => $project->group_name,
        ];
    }
}
