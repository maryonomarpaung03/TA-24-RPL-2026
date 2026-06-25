<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjekSayaController extends Controller
{
    public function index()
    {
        $searchHistory = [];
        $currentUserId = (int) Auth::id();

        $ownedIds = Project::query()
            ->where('created_by', $currentUserId)
            ->pluck('id');

        $memberIds = DB::table('project_members')
            ->where('user_id', $currentUserId)
            ->pluck('project_id');

        $keyword = request('search');

        $searchHistory = session(
            'search_history',
            []
        );

        if (!empty($keyword)) {

            array_unshift(
                $searchHistory,
                $keyword
            );

            $searchHistory = array_unique(
                $searchHistory
            );

            $searchHistory = array_slice(
                $searchHistory,
                0,
                5
            );

            session([
                'search_history' => $searchHistory
            ]);
        }


        $projectQuery = Project::query()
            ->whereIn(
                'id',
                $ownedIds->merge($memberIds)->unique()
            );

        if (!empty($keyword)) {
            $projectQuery->where(
                'title',
                'LIKE',
                '%' . $keyword . '%'
            );
        }

        $projectData = $projectQuery
            ->orderByDesc('created_at')
            ->get();

        $projects = $projectData->map(function ($project) use ($currentUserId) {
            $statusMap = [
                'draft' => 'Draft',
                'pending_approval' => 'In Review',
                'pending_revision' => 'Review Perubahan',
                'active' => 'In Progress',
                'completed' => 'Done',
                'rejected' => 'Rejected',
                'archived' => 'Archived',
            ];

            $filterMap = [
                'draft' => 'draft',
                'pending_approval' => 'on_review',
                'pending_revision' => 'on_review',
                'active' => 'in_progress',
                'completed' => 'done',
                'rejected' => 'planning',
                'archived' => 'planning',
            ];

            $uiStatus = $statusMap[$project->status] ?? 'Planning';
            $filterKey = $filterMap[$project->status] ?? 'planning';
            $members = ProjectAccess::memberInitials($project->id);
            $memberCount = max(1, count($members));

            return [
                'id' => $project->id,
                'name' => $project->title,
                'status' => $uiStatus,
                'label' => $uiStatus,
                'filter_key' => $filterKey,
                'db_status' => $project->status,
                'progress' => match ($project->status) {
                    'draft' => 10,
                    'pending_approval' => 25,
                    'pending_revision' => 55,
                    'active' => 60,
                    'completed' => 100,
                    default => 0,
                },
                'can_manage' => (int) $project->created_by === $currentUserId,
                'description' => ProjectAccess::shortDescription($project->description),
                'created_at' => Carbon::parse($project->created_at)->format('d/m/Y'),
                'member_count' => $memberCount,
                'members' => $members,
                'lecturer_email' => $project->lecturer_email,
            ];
        })->toArray();

        return view('ProjekSaya', compact('projects', 'searchHistory'));
    }
}
