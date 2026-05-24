<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenStudentProjectsController extends Controller
{
    /** @var list<string> */
    private const APPROVED_STATUSES = ['active', 'completed'];

    public function index()
    {
        $this->ensureLecturer();

        $email = strtolower(trim((string) Auth::user()->email));

        $projects = Project::query()
            ->where('lecturer_email', $email)
            ->whereIn('status', self::APPROVED_STATUSES)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Project $p) => $this->mapListRow($p));

        return view('DosenStudentProjects', [
            'approved_projects' => $projects,
        ]);
    }

    public function show(int $id)
    {
        $this->ensureLecturer();

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        if (! in_array($project->status, self::APPROVED_STATUSES, true)) {
            return redirect()
                ->route('dosen.proyek-mahasiswa')
                ->with('error', 'Proyek ini belum disetujui atau masih menunggu persetujuan.');
        }

        return view('DosenStudentProjectDetail', [
            'project' => $this->mapDetailRow($project),
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
            'status_label' => $project->status === 'completed' ? 'Selesai' : 'Berjalan',
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

        return [
            'id' => $project->id,
            'name' => $project->title,
            'status' => $project->status,
            'status_label' => $project->status === 'completed' ? 'Selesai' : 'Berjalan',
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
        ];
    }
}
