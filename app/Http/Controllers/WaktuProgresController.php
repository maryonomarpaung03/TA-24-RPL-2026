<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaktuProgresController extends Controller
{
    public function index(Request $request, $id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $selectedMonth = (int) $request->get('month', (int) now()->format('n'));
        $selectedYear = (int) $request->get('year', (int) now()->format('Y'));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $monthName = Carbon::create($selectedYear, $selectedMonth)->translatedFormat('F');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $team = array_map(
            fn (array $m) => [
                'name' => $m['name'],
                'role' => $m['role'],
                'initials' => $m['initials'],
            ],
            \App\Support\ProjectAccess::teamMembersForProject((int) $id)
        );

        return view('WaktuProgres', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'daysInMonth' => $daysInMonth,
            'monthName' => $monthName,
            'tasks' => [],
            'months' => $months,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'progressMilestone' => 0,
            'completedTasks' => 0,
            'totalTasks' => 0,
            'milestones' => [],
            'deadlineCard' => null,
            'team' => $team,
        ]);
    }
}
