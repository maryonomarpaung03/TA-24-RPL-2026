<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(
        Request $request
    )
    {
        /*
        user login
        */
        $loggedUser =
            Auth::user();

        /*
        initials otomatis
        */
        $initials = 'U';

        if (
            $loggedUser &&
            !empty(
                $loggedUser->full_name
            )
        ) {

            $words = explode(
                ' ',
                trim(
                    $loggedUser
                        ->full_name
                )
            );

            $initials =
                strtoupper(
                    substr(
                        $words[0],
                        0,
                        1
                    ) .
                    (
                        isset(
                            $words[1]
                        )
                        ? substr(
                            $words[1],
                            0,
                            1
                        )
                        : ''
                    )
                );
        }

        /*
        user data
        */
        $user = [
            'name' =>
                $loggedUser
                    ->full_name
                ?? 'User',

            'role' =>
                $loggedUser
                    ->role
                ?? 'student',

            'initials' =>
                $initials,

            'notif_count' =>
                3
        ];

        /*
        statistik project
        */
        $statistics = [

            'total_projects' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )->count(),

            'projects_in_progress' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->count(),

            'completed_projects' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->where(
                    'status',
                    'completed'
                )
                ->count(),

            'pending_approval' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->where(
                    'status',
                    'pending_approval'
                )
                ->count(),
        ];

        /*
        pie chart
        */
        $pie_chart_data = [

            'Ongoing' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->count(),

            'Planning' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->whereIn(
                    'status',
                    [
                        'draft',
                        'pending_approval'
                    ]
                )
                ->count(),

            'Completed' =>
                Project::where(
                    'created_by',
                    $loggedUser->id
                )
                ->where(
                    'status',
                    'completed'
                )
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
            ->filter(function (
                $task
            ) {

                $daysLeft =
                    now()->diffInDays(
                        $task->due_date,
                        false
                    );

                return
                    $daysLeft <= 7
                    && $daysLeft >= 0;
            })
            ->map(function (
                $task
            ) {

                $daysLeft =
                    now()->diffInDays(
                        $task->due_date,
                        false
                    );

                return [
                    'task' =>
                        $task
                            ->task_title,

                    'project' =>
                        $task
                            ->title,

                    'days_left' =>
                        $daysLeft,

                    'priority' =>
                        $daysLeft <= 2
                        ? 'red'
                        : (
                            $daysLeft <= 5
                            ? 'yellow'
                            : 'gray'
                        )
                ];
            })
            ->values()
            ->toArray();

        /*
        selected project
        */
        $selected_project =
            null;

        $projectId =
            $request->query(
                'project_id'
            );

        $initialEditMode =
            $request->query(
                'mode'
            ) === 'edit';

        if (
            $projectId !== null
            &&
            $projectId !== ''
        ) {

            $selected_project =
                ProjectCatalog::find(
                    $projectId
                );
        }

        return view(
            'dashboard',
            compact(
                'user',
                'statistics',
                'pie_chart_data',
                'bar_chart_data',
                'ongoing_projects',
                'deadlines',
                'selected_project',
                'initialEditMode'
            )
        );
    }
}