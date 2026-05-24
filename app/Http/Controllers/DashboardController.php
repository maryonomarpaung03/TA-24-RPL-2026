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
    public function index(
    Request $request
)
{
    $loggedUser =
        Auth::user();

    if (
        !$loggedUser
    ) {

        return redirect()
            ->route(
                'login'
            );
    }

    try {

    $user =
    $this->buildUserPayload(
        $loggedUser
    );

} catch (
    \Throwable $e
) {

    dd(
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
}
    
    return view(
        'dashboard',
        [
            'user' => $user,
            'statistics' => [],
            'pie_chart_data' => [],
            'bar_chart_data' => [],
            'ongoing_projects' => [],
            'deadlines' => [],
            'selected_project' => null,
            'initialEditMode' => false,
            'problemBoard' => $this->emptyProblemBoard(),
            'problemComments' => [],
            'teamMembers' => [],
            'participantCount' => 1,
        ]
    );
    
    }
    public function problemIdentification(
    int $id
)
{
    $loggedUser =
        Auth::user();

    if (
        $loggedUser &&
        $loggedUser->role
        === 'lecturer'
    ) {
        return redirect()->route(
            'dosen.dashboard'
        );
    }

    $selected_project =
        ProjectCatalog::find($id);

    if (! $selected_project) {

        return redirect()
            ->route(
                'projek-saya'
            )
            ->with(
                'error',
                'Proyek tidak ditemukan atau Anda tidak memiliki akses.'
            );
    }

    
    /*
    ======================
    TEAM MEMBERS
    ======================
    */
    $teamMembers =
        ProjectAccess::teamMembersForProject(
            (int)
            $selected_project['id']
        );

    $participantCount =
        max(
            1,
            count($teamMembers)
        );

    /*
    ======================
    LOAD PROBLEM BOARD
    ======================
    */
    $problems =
        DB::table(
            'problem_identifications'
        )
        ->where(
            'project_id',
            $selected_project['id']
        )
        ->orderBy(
            'created_at',
            'desc'
        )
        ->get();

    $problemBoard = [
        'ide' => [],
        'voting' => [],
        'diajukan' => [],
        'perbaiki' => [],
        'selesai' => [],
    ];

    foreach (
        $problems
        as $problem
    ) {

        /*
        count vote
        */
        $voteCount =
            DB::table(
                'problem_votes'
            )
            ->where(
                'problem_id',
                $problem->id
            )
            ->count();

        /*
        map status
        */
        $mappedBoard =
            match (
                $problem->board_status
            ) {

                'idea'
                    => 'ide',

                'voting'
                    => 'voting',

                'submitted'
                    => 'diajukan',

                'revision'
                    => 'perbaiki',

                'done'
                    => 'selesai',

                default
                    => 'ide'
            };

        $problemBoard[
            $mappedBoard
        ][] = [

            'id' =>
                $problem->id,

            'title' =>
                $problem->title,

            'description' =>
                $problem
                    ->description,

            'category' =>
                $problem
                    ->category,

            'priority' =>
                $problem
                    ->priority,

            'attachment_link' =>
                $problem
                    ->attachment_link,

            'votes' =>
                $voteCount,

            'voting_open' =>
                (bool)
                $problem
                    ->voting_open,

            'deadline' =>
                $problem
                    ->voting_deadline,

            'feedback' =>
                $problem
                    ->lecturer_feedback,

            'created_by' =>
                $problem
                    ->created_by,
        ];
    }

    /*
    ======================
    COMMENTS
    ======================
    */
    $problemComments =
        DB::table(
            'discussions'
        )
        ->join(
            'users',
            'users.id',
            '=',
            'discussions.user_id'
        )
        ->where(
            'project_id',
            $selected_project['id']
        )
        ->whereNull(
            'task_id'
        )
        ->orderBy(
            'created_at'
        )
        ->get([
            'discussions.id',
            'message',
            'full_name',
            'discussions.created_at'
        ])
        ->map(
            function (
                $row
            ) {

                return [

                    'id' =>
                        $row->id,

                    'author' =>
                        $row
                        ->full_name,

                    'text' =>
                        $row
                        ->message,

                    'created_at' =>
                        $row
                        ->created_at,
                ];
            }
        )
        ->toArray();

    return view(
        'dashboard',
        [

            'user' =>
                $this
                ->buildUserPayload(
                    $loggedUser
                ),

            'statistics' =>
                [],

            'pie_chart_data' =>
                [],

            'bar_chart_data' =>
                [],

            'ongoing_projects' =>
                [],

            'deadlines' =>
                [],

            'selected_project' =>
                $selected_project,

            'initialEditMode' =>
                false,

            'problemBoard' =>
                $problemBoard,

            'problemComments' =>
                $problemComments,

            'teamMembers' =>
                $teamMembers,

            'participantCount' =>
                $participantCount,
        ]
    );
}

public function storeProblem(
    Request $request,
    int $id
)
{
    $user =
        Auth::user();

    $validated =
        $request->validate([

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'category' => [
                'required',
                'in:Teknik,Diskusi,Etika,Kebutuhan Proyek'
            ],

            'priority' => [
                'required',
                'in:Rendah,Sedang,Tinggi'
            ],

            'attachment' => [
                'nullable',
                'string'
            ],
        ]);

    $problemId =
        DB::table(
            'problem_identifications'
        )
        ->insertGetId([

            'project_id' =>
                $id,

            'created_by' =>
                $user->id,

            'title' =>
                $validated['title'],

            'description' =>
                $validated['description']
                ?? null,

            'category' =>
                $validated['category'],

            'priority' =>
                $validated['priority'],

            'attachment_link' =>
                $validated['attachment']
                ?? null,

            'board_status' =>
                'idea',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

    return response()->json([
        'success' => true,

        'card' => [
            'id' => $problemId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'attachment' => $validated['attachment'] ?? '-',
            'checklist' => '0/3',
        ]
    ]);
}
public function moveProblem(
    Request $request
)
{
    $request->validate([

        'problem_id' => [
            'required',
            'integer'
        ],

        'status' => [
            'required',
            'in:idea,voting,submitted,revision,done'
        ],
    ]);

    DB::table(
        'problem_identifications'
    )
    ->where(
        'id',
        $request->problem_id
    )
    ->update([

        'board_status' =>
            $request->status,

        'updated_at' =>
            now()
    ]);

    return response()->json([
        'success' => true
    ]);
}

private function buildUserPayload(
    $loggedUser
): array
{
    $initials = 'U';

    if (
        $loggedUser
        &&
        !empty(
            $loggedUser->full_name
        )
    ) {

        $words = explode(
            ' ',
            trim(
                $loggedUser->full_name
            )
        );

        $initials = strtoupper(
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

    return [

        'name' =>
            $loggedUser
                ->full_name
            ?? $loggedUser
                ->name
            ?? 'User',

        'role' =>
            $loggedUser
                ->role
            ?? 'student',

        'initials' =>
            $initials,

        'notif_count' =>
            3,
    ];
}

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
}