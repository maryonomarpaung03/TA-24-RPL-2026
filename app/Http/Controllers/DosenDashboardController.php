<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenDashboardController extends Controller
{
  public function index()
  {
    if (Auth::user()->role !== 'lecturer') {
      abort(403, 'Halaman ini hanya untuk dosen.');
    }

    $email = strtolower(trim((string) Auth::user()->email));

    $statistics = [
      'total_proyek' => DB::table('projects')->where('lecturer_email', $email)->count(),
      'proyek_berjalan' => DB::table('projects')->where('lecturer_email', $email)->where('status', 'active')->count(),
      'mahasiswa_kelas' => 0,
      'mata_kuliah' => 0,
    ];

    $pending_approvals = DB::table('projects')
      ->join('users', 'users.id', '=', 'projects.created_by')
      ->where('projects.lecturer_email', $email)
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
          ? \Carbon\Carbon::parse($row->submitted_at)->format('d M Y')
          : '-',
      ])
      ->all();

    $problem_voting_notifications = DB::table('project_notifications')
      ->join('projects', 'projects.id', '=', 'project_notifications.project_id')
      ->leftJoin('problem_identifications', function ($join) {
        $join->on('problem_identifications.project_id', '=', 'projects.id')
          ->where('problem_identifications.board_status', '=', 'submitted');
      })
      ->where('project_notifications.recipient_email', $email)
      ->whereIn('project_notifications.type', ['problem_submitted_for_review', 'problem_resubmitted'])
      ->orderByDesc('project_notifications.created_at')
      ->select(
        'project_notifications.id',
        'projects.name as project_name',
        'projects.id as project_id',
        'problem_identifications.title as problem_title',
        'project_notifications.created_at'
      )
      ->limit(5)
      ->get()
      ->map(fn ($row) => [
        'id' => $row->id,
        'project_id' => $row->project_id,
        'project_name' => $row->project_name,
        'problem_title' => $row->problem_title ?? 'Masalah utama',
        'time_ago' => \Carbon\Carbon::parse($row->created_at)->diffForHumans(),
      ])
      ->all();

    $pending_total = DB::table('projects')
      ->where('lecturer_email', $email)
      ->where('status', 'pending_approval')
      ->count();

    $notifications_total = DB::table('project_notifications')
      ->where('recipient_email', $email)
      ->whereNull('read_at')
      ->count();

    return view('DosenDashboard', compact(
      'statistics',
      'pending_approvals',
      'problem_voting_notifications',
      'pending_total',
      'notifications_total'
    ));
  }
}
