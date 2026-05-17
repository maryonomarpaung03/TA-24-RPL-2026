<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $loggedUser = Auth::user();

        if ($loggedUser && $loggedUser->role === 'lecturer') {
            return redirect()->route('dosen.dashboard');
        }

        $legacyProjectId = $request->query('project_id');
        if ($legacyProjectId !== null && $legacyProjectId !== '') {
            return redirect()->route('problem-identification', $legacyProjectId);
        }

        $user = $this->buildUserPayload($loggedUser);

        $statistics = [
            'total_projects' => Project::where('created_by', $loggedUser->id)->count(),
            'projects_in_progress' => Project::where('created_by', $loggedUser->id)
                ->where('status', 'active')
                ->count(),
            'completed_projects' => Project::where('created_by', $loggedUser->id)
                ->where('status', 'completed')
                ->count(),
            'pending_approval' => Project::where('created_by', $loggedUser->id)
                ->where('status', 'pending_approval')
                ->count(),
        ];

        $pie_chart_data = [
            'Ongoing' => Project::where('created_by', $loggedUser->id)
                ->where('status', 'active')
                ->count(),
            'Planning' => Project::where('created_by', $loggedUser->id)
                ->whereIn('status', ['draft', 'pending_approval'])
                ->count(),
            'Completed' => Project::where('created_by', $loggedUser->id)
                ->where('status', 'completed')
                ->count(),
        ];

        /*
        sementara dummy
        nanti ambil dari tasks
        */
        $bar_chart_data = [
            'To Do' => 4,
            'In Progress' => 3,
            'Done' => 1
        ];

        /*
        project ongoing
        */
        $ongoing_projects =
            Project::where(
                'created_by',
                $loggedUser->id
            )
            ->where(
                'status',
                'active'
            )
            ->latest()
            ->take(5)
            ->get()
            ->map(function (
                $project
            ) {

                return [
                    'name' =>
                        $project
                            ->title,

                    'deadline' =>
                        $project
                            ->end_date
                        ? \Carbon\Carbon::parse(
                            $project
                                ->end_date
                        )->format(
                            'd M Y'
                        )
                        : '-',

                    'progress' =>
                        60
                ];
            })
            ->toArray();

        /*
        deadline task <= 7 hari
        */
        $deadlines =
            DB::table('tasks')
            ->join(
                'projects',
                'tasks.project_id',
                '=',
                'projects.id'
            )
            ->where(
                'projects.created_by',
                $loggedUser->id
            )
            ->whereNotNull(
                'tasks.due_date'
            )
            ->get()
            ->filter(function ($task) {
                $daysLeft = now()->diffInDays($task->due_date, false);

                return $daysLeft <= 7 && $daysLeft >= 0;
            })
            ->map(function ($task) {
                $daysLeft = now()->diffInDays($task->due_date, false);

                return [
                    'task' => $task->task_title,
                    'project' => $task->title,
                    'days_left' => $daysLeft,
                    'priority' => $daysLeft <= 2 ? 'red' : ($daysLeft <= 5 ? 'yellow' : 'gray'),
                ];
            })
            ->values()
            ->toArray();

        return view('dashboard', [
            'user' => $user,
            'statistics' => $statistics,
            'pie_chart_data' => $pie_chart_data,
            'bar_chart_data' => $bar_chart_data,
            'ongoing_projects' => $ongoing_projects,
            'deadlines' => $deadlines,
            'selected_project' => null,
            'initialEditMode' => false,
            'problemBoard' => $this->emptyProblemBoard(),
            'problemComments' => [],
            'teamMembers' => [],
            'participantCount' => 1,
        ]);
    }

    public function problemIdentification(int $id)
    {
        $loggedUser = Auth::user();

        if ($loggedUser && $loggedUser->role === 'lecturer') {
            return redirect()->route('dosen.dashboard');
        }

        $selected_project = ProjectCatalog::find($id);

        if (! $selected_project) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $teamMembers = ProjectAccess::teamMembersForProject((int) $selected_project['id']);
        $participantCount = max(1, count($teamMembers));

        return view('dashboard', [
            'user' => $this->buildUserPayload($loggedUser),
            'statistics' => [],
            'pie_chart_data' => [],
            'bar_chart_data' => [],
            'ongoing_projects' => [],
            'deadlines' => [],
            'selected_project' => $selected_project,
            'initialEditMode' => false,
            'problemBoard' => $this->emptyProblemBoard(),
            'problemComments' => [],
            'teamMembers' => $teamMembers,
            'participantCount' => $participantCount,
        ]);
    }

    /**
     * @return array{ide: array, voting: array, diajukan: array, perbaiki: array, selesai: array}
     */
    private function emptyProblemBoard(): array
    {
        return [
            'ide' => [],
            'voting' => [],
            'diajukan' => [],
            'perbaiki' => [],
            'selesai' => [],
        ];
    }

    private function buildUserPayload($loggedUser): array
    {
        $initials = 'U';

        if ($loggedUser && ! empty($loggedUser->full_name)) {
            $words = explode(' ', trim($loggedUser->full_name));
            $initials = strtoupper(
                substr($words[0], 0, 1).
                (isset($words[1]) ? substr($words[1], 0, 1) : '')
            );
        }

        return [
            'name' => $loggedUser->full_name ?? 'User',
            'role' => $loggedUser->role ?? 'student',
            'initials' => $initials,
            'notif_count' => 3,
        ];
    }
}
