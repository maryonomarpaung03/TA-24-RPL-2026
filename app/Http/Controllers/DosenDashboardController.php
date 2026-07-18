<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenDashboardController extends Controller
{
  /** Rentang waktu => jumlah hari ke belakang. */
  private const PERIODS = [
    '7' => '7 hari terakhir',
    '30' => '30 hari terakhir',
    '90' => '90 hari terakhir',
  ];

  public function index(Request $request)
  {
    if (Auth::user()->role !== 'lecturer') {
      abort(403, 'Halaman ini hanya untuk dosen.');
    }

    $email = strtolower(trim((string) Auth::user()->email));

    $classId = (string) $request->query('kelas', '');
    $period = (string) $request->query('periode', '');
    $since = isset(self::PERIODS[$period])
      ? Carbon::now()->subDays((int) $period)
      : null;

    // Kelas milik dosen dipakai sebagai opsi filter.
    $classOptions = DB::table('academic_classes')
      ->where('lecturer_id', Auth::id())
      ->orderBy('name')
      ->pluck('name', 'id')
      ->all();

    $projects = fn () => DB::table('projects')
      ->where('projects.lecturer_email', $email)
      ->when($classId !== '', fn ($q) => $q->where('projects.academic_class_id', $classId))
      ->when($since, fn ($q) => $q->where('projects.created_at', '>=', $since));

    $statistics = [
      'total_proyek' => $projects()->count(),
      'proyek_berjalan' => $projects()->where('projects.status', 'active')->count(),
      'mahasiswa_kelas' => DB::table('class_members')
        ->whereIn('academic_class_id', $classId !== '' ? [$classId] : array_keys($classOptions))
        ->distinct()
        ->count('user_id'),
      'mata_kuliah' => $projects()->distinct()->count('projects.course_name'),
    ];

    $pending_approvals = $projects()
      ->join('users', 'users.id', '=', 'projects.created_by')
      ->where('projects.status', 'pending_approval')
      ->orderByDesc('projects.submitted_at')
      ->select(
        'projects.id',
        'projects.name as name',
        'users.full_name as creator_name',
        'projects.course_name as course',
        'projects.submitted_at'
      )
      ->limit(5)
      ->get()
      ->map(fn ($row) => [
        'id' => $row->id,
        'name' => $row->name,
        'creator_name' => $row->creator_name ?? 'Mahasiswa',
        'course' => $row->course ?? '-',
        'submitted_at' => $row->submitted_at
          ? Carbon::parse($row->submitted_at)->format('d M Y')
          : '-',
      ])
      ->all();

    $problem_voting_notifications = DB::table('project_notifications as notification')
      ->join('projects', 'projects.id', '=', 'notification.project_id')
      ->leftJoin('problem_identifications', function ($join) {
        $join->on('problem_identifications.project_id', '=', 'projects.id')
          ->where('problem_identifications.board_status', '=', 'submitted');
      })
      ->where('notification.recipient_email', $email)
      ->whereIn('notification.type', ['problem_submitted_for_review', 'problem_resubmitted'])
      ->when($classId !== '', fn ($q) => $q->where('projects.academic_class_id', $classId))
      ->when($since, fn ($q) => $q->where('notification.created_at', '>=', $since))
      ->groupBy('projects.id', 'projects.name', 'problem_identifications.title')
      ->orderByDesc(DB::raw('MAX(notification.created_at)'))
      ->selectRaw('projects.name as project_name, projects.id as project_id, problem_identifications.title as problem_title, MAX(notification.created_at) as created_at')
      ->limit(5)
      ->get()
      ->map(fn ($row) => [
    'id' => $row->project_id,
    'project_id' => $row->project_id,
    'project_name' => $row->project_name,
    'problem_title' => $row->problem_title ?? 'Masalah utama',
    'time_ago' => Carbon::parse($row->created_at)->diffForHumans(),
    ])
      ->all();

    $pending_total = $projects()->where('projects.status', 'pending_approval')->count();

    $notifications_total = DB::table('project_notifications')
      ->where('recipient_email', $email)
      ->whereNull('read_at')
      ->count();

    $filterState = ['kelas' => $classId, 'periode' => $period];
    $periodOptions = self::PERIODS;

    return view('DosenDashboard', compact(
      'statistics',
      'pending_approvals',
      'problem_voting_notifications',
      'pending_total',
      'notifications_total',
      'filterState',
      'classOptions',
      'periodOptions'
    ));
  }
}
