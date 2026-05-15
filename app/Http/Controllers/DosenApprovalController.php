<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenApprovalController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }

        $email = strtolower(trim((string) Auth::user()->email));

        $pending = Project::query()
            ->where('lecturer_email', $email)
            ->where('status', 'pending_approval')
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (Project $p) => $this->mapListRow($p));

        return view('DosenApproval', [
            'pending_projects' => $pending,
        ]);
    }

    public function show(int $id)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403);
        }

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        return view('DosenApprovalDetail', [
            'project' => $this->mapDetailRow($project),
        ]);
    }

    public function approve(int $id)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403);
        }

        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
            abort(403);
        }

        if ($project->status !== 'pending_approval') {
            return back()->with('error', 'Proyek ini tidak dalam status menunggu persetujuan.');
        }

        $project->update([
            'status' => 'active',
        ]);

        $creatorEmail = DB::table('users')->where('id', $project->created_by)->value('email');

        if ($creatorEmail) {
            DB::table('project_notifications')->insert([
                'project_id' => $project->id,
                'recipient_email' => strtolower($creatorEmail),
                'type' => 'project_approved',
                'title' => 'Proyek disetujui dosen',
                'message' => 'Proyek "'.$project->title.'" telah disetujui. Anda dapat melanjutkan ke tahap PjBL.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('dosen.persetujuan.show', $project->id)
            ->with('success', 'Proyek "'.$project->title.'" berhasil disetujui. Mahasiswa dapat melanjutkan PjBL.');
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

        $parsed = ProjectAccess::parseProjectDescription($project->description);

        return [
            'id' => $project->id,
            'name' => $project->title,
            'description' => $parsed['deskripsi'] ?: $project->description,
            'submitted_at' => $project->submitted_at?->format('d M Y H:i'),
            'creator_name' => $creator->full_name ?? $creator->name ?? '-',
            'creator_email' => $creator->email ?? '-',
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
        $attachmentUrl = null;

        if (preg_match('/\[Lampiran: ([^\]]+)\]/', (string) $project->description, $m)) {
            $attachmentUrl = trim($m[1]);
        }

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

        return [
            'id' => $project->id,
            'name' => $project->title,
            'status' => $project->status,
            'group_name' => $project->group_name,
            'course_name' => $project->course_name,
            'masalah' => $parsed['masalah'],
            'deskripsi' => $parsed['deskripsi'],
            'planned_months' => $project->planned_months,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'lecturer_name' => $project->lecturer_name,
            'lecturer_email' => $project->lecturer_email,
            'submitted_at' => $project->submitted_at?->format('d M Y H:i'),
            'creator_name' => $creator->full_name ?? $creator->name ?? '-',
            'creator_email' => $creator->email ?? '-',
            'members' => $members,
            'attachment_url' => $attachmentUrl,
        ];
    }
}
