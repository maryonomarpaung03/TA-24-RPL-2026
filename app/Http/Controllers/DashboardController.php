<?php

namespace App\Http\Controllers;

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
    public function problemIdentification(int $id)
    {
        $loggedUser = Auth::user();

        if ($loggedUser && $loggedUser->role === 'lecturer') {
            return redirect()->route('dosen.dashboard');
        }

        $pageData = ProblemIdentificationController::pageData($id);

        if (! $pageData['selected_project']) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return view('dashboard', array_merge([
            'user' => $this->buildUserPayload($loggedUser),
            'statistics' => [],
            'pie_chart_data' => [],
            'bar_chart_data' => [],
            'ongoing_projects' => [],
            'deadlines' => [],
            'initialEditMode' => false,
        ], $pageData));
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