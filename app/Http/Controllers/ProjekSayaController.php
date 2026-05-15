<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjekSayaController extends Controller
{
    public function index()
    {
        $currentUserId = Auth::id();

        $searchHistory = [];

        // Ambil hanya project milik user
        $projectData = Project::where(
                'created_by',
                $currentUserId
            )
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform data ke format blade
        $projects = $projectData->map(
            function ($project, $index) {

                // Mapping status DB -> UI
                $statusMap = [
                    'draft' => 'Planning',
                    'pending_approval' => 'Planning',
                    'active' => 'In Progress',
                    'completed' => 'Done',
                    'rejected' => 'Rejected',
                    'archived' => 'Archived',
                ];

                $uiStatus =
                    $statusMap[$project->status]
                    ?? 'Planning';

                /*
                Member lewat skema DB: project_groups → group_members (bukan project_members).
                */
                $memberCount = (int) DB::table('group_members')
                    ->join(
                        'project_groups',
                        'group_members.group_id',
                        '=',
                        'project_groups.id'
                    )
                    ->where('project_groups.project_id', $project->id)
                    ->selectRaw('COUNT(DISTINCT group_members.user_id) as aggregate')
                    ->value('aggregate');

                $memberRows = DB::table('group_members')
                    ->join(
                        'project_groups',
                        'group_members.group_id',
                        '=',
                        'project_groups.id'
                    )
                    ->join('users', 'group_members.user_id', '=', 'users.id')
                    ->where('project_groups.project_id', $project->id)
                    ->select(
                        'group_members.user_id',
                        'users.full_name',
                        'users.name'
                    )
                    ->orderBy('group_members.user_id')
                    ->get()
                    ->unique('user_id')
                    ->take(3)
                    ->values();

                $members = $memberRows
                    ->map(fn ($row) => $this->initialsFromUserRow($row))
                    ->toArray();

                /*
                Belum ada grup/anggota: tampilkan pembuat proyek sebagai satu anggota.
                */
                if ($memberCount === 0 && $project->created_by) {
                    $creator = DB::table('users')
                        ->where('id', $project->created_by)
                        ->select('full_name', 'name')
                        ->first();

                    if ($creator) {
                        $memberCount = 1;
                        $members = [$this->initialsFromUserRow($creator)];
                    }
                }

                return [

                    'id' => $project->id,

                    'name' =>
                        $project->title,

                    'status' =>
                        $uiStatus,

                    'label' =>
                        $uiStatus,

                    /*
                    Progress sementara
                    */
                    'progress' => match (
                        $project->status
                    ) {
                        'draft' => 10,
                        'pending_approval' => 25,
                        'active' => 60,
                        'completed' => 100,
                        default => 0
                    },

                    'description' =>
                        $project->description
                        ?? 'Belum ada deskripsi proyek.',

                    'created_at' =>
                        Carbon::parse(
                            $project->created_at
                        )->format('d/m/Y'),

                    'member_count' =>
                        $memberCount,

                    'members' =>
                        $members,

                    /*
                    project terbaru featured
                    */
                    'featured' =>
                        $index === 0,

                    'logo' =>
                        $project->logo
                ];
            }
        )->toArray();

        return view(
            'ProjekSaya',
            compact(
                'projects',
                'searchHistory'
            )
        );
    }

    private function initialsFromUserRow(object $row): string
    {
        $display = trim((string) (($row->full_name ?? null) ?: ($row->name ?? '')));

        $words = preg_split('/\s+/', $display, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return 'U';
    }
}