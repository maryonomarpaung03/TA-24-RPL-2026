<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjekSayaController extends Controller
{
    public function index()
    {
        /*
        sementara pakai akun Maryono
        nanti diganti Auth::id()
        */
        $currentUserId = 2;

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
                Hitung jumlah member
                */
                $memberCount = DB::table(
                        'project_members'
                    )
                    ->where(
                        'project_id',
                        $project->id
                    )
                    ->count();

                /*
                Ambil inisial member
                */
                $members = DB::table(
                        'project_members'
                    )
                    ->join(
                        'users',
                        'project_members.user_id',
                        '=',
                        'users.id'
                    )
                    ->where(
                        'project_members.project_id',
                        $project->id
                    )
                    ->select(
                        'users.full_name'
                    )
                    ->limit(3)
                    ->get()
                    ->map(function ($member) {

                        $words = explode(
                            ' ',
                            $member->full_name
                        );

                        return strtoupper(
                            substr(
                                $words[0],
                                0,
                                1
                            ) .
                            (
                                isset($words[1])
                                ? substr(
                                    $words[1],
                                    0,
                                    1
                                )
                                : ''
                            )
                        );
                    })
                    ->toArray();

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
}