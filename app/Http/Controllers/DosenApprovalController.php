<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenApprovalController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }

        $email = strtolower(trim((string) Auth::user()->email));

        $base = Project::query()
            ->where('lecturer_email', $email)
            ->whereIn('status', ['pending_approval', 'pending_revision']);

        $total = (clone $base)->count();
        $all = (clone $base)->get();

        $keyword = trim((string) $request->query('q', ''));
        $classId = (string) $request->query('kelas', '');
        $jenis = (string) $request->query('jenis', '');
        $urut = (string) $request->query('urut', 'terbaru');

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

        if ($jenis !== '') {
            $query->where('status', $jenis);
        }

        $pending = $query
            ->orderBy('submitted_at', $urut === 'terlama' ? 'asc' : 'desc')
            ->get()
            ->map(fn (Project $p) => $this->mapListRow($p));

        $classIds = $all->pluck('academic_class_id')->filter()->unique();

        return view('DosenApproval', [
            'pending_projects' => $pending,
            'totalPending' => $total,
            'filterState' => [
                'q' => $keyword,
                'kelas' => $classId,
                'jenis' => $jenis,
                'urut' => $urut,
            ],
            'classOptions' => $classIds->isEmpty() ? [] : DB::table('academic_classes')
                ->whereIn('id', $classIds)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
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

        if (! in_array($project->status, ['pending_approval', 'pending_revision'], true)) {
            return back()->with('error', 'Proyek ini tidak dalam status menunggu persetujuan.');
        }

        $wasRevision = $project->status === 'pending_revision';

        $project->update([
            'status' => 'active',
        ]);

        $creatorEmail = DB::table('users')->where('id', $project->created_by)->value('email');

        if ($creatorEmail) {
            DB::table('project_notifications')->insert([
                'project_id' => $project->id,
                'recipient_email' => strtolower($creatorEmail),
                'type' => $wasRevision ? 'project_revision_approved' : 'project_approved',
                'title' => $wasRevision ? 'Perubahan proyek disetujui' : 'Proyek disetujui dosen',
                'message' => $wasRevision
                    ? 'Dosen menyetujui perubahan pada proyek "'.$project->title.'".'
                    : 'Proyek "'.$project->title.'" telah disetujui. Anda dapat melanjutkan ke tahap PjBL.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $successMessage = $wasRevision
            ? 'Perubahan proyek "'.$project->title.'" disetujui. Tim dapat melanjutkan PjBL.'
            : 'Proyek "'.$project->title.'" berhasil disetujui. Mahasiswa dapat melanjutkan PjBL.';

        return redirect()
            ->route('dosen.proyek-mahasiswa.show', $project->id)
            ->with('success', $successMessage);
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
            'status' => $project->status,
            'status_label' => $project->status === 'pending_revision' ? 'Review Perubahan' : 'Menunggu Persetujuan',
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
