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

        $projectData = Project::query()
            ->whereIn('id', $ownedIds->merge($memberIds)->unique())
            ->orderByDesc('created_at')
            ->get();

        $projects = $projectData->map(function ($project, $index) {
            $statusMap = [
                'draft' => 'Draft',
                'pending_approval' => 'In Review',
                'active' => 'In Progress',
                'completed' => 'Done',
                'rejected' => 'Rejected',
                'archived' => 'Archived',
            ];

            $filterMap = [
                'draft' => 'draft',
                'pending_approval' => 'on_review',
                'active' => 'in_progress',
                'completed' => 'done',
                'rejected' => 'planning',
                'archived' => 'planning',
            ];

            $uiStatus = $statusMap[$project->status] ?? 'Planning';
            $filterKey = $filterMap[$project->status] ?? 'planning';
            $members = ProjectAccess::memberInitials($project->id);
            $memberCount = max(1, count($members));
            $media = ProjectAccess::projectMediaPreview($project->logo, $project->description);

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
                    'active' => 60,
                    'completed' => 100,
                    default => 0,
                },
                'description' => ProjectAccess::shortDescription($project->description),
                'created_at' => Carbon::parse($project->created_at)->format('d/m/Y'),
                'member_count' => $memberCount,
                'members' => $members,
                'featured' => $index === 0,
                'preview_url' => $media['preview_url'],
                'attachment_url' => $media['attachment_url'],
                'attachment_kind' => $media['attachment_kind'],
                'has_media' => $media['has_media'],
                'lecturer_email' => $project->lecturer_email,
            ];
        })->toArray();

        return view('ProjekSaya', compact('projects', 'searchHistory'));
    }
}
