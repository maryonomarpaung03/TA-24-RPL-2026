<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProblemIdentificationService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenProblemReviewController extends Controller
{
  public function __construct(
    private ProblemIdentificationService $service
  ) {}

  public function show(int $id)
  {
    if (Auth::user()->role !== 'lecturer') {
      abort(403);
    }

    $project = Project::query()->findOrFail($id);

    if (! ProjectAccess::lecturerCanView(Auth::user(), $project)) {
      abort(403);
    }

    $pending = DB::table('problem_identifications')
      ->leftJoin('users', 'users.id', '=', 'problem_identifications.created_by')
      ->where('problem_identifications.project_id', $id)
      ->whereIn('problem_identifications.board_status', ['submitted', 'revision', 'done'])
      ->orderByRaw("FIELD(problem_identifications.board_status, 'submitted', 'revision', 'done')")
      ->select(
        'problem_identifications.*',
        'users.full_name as author_name'
      )
      ->get();

    $activeReview = $pending->firstWhere('board_status', 'submitted');

    $parsed = ProjectAccess::parseProjectDescription($project->description);
    $creator = DB::table('users')->where('id', $project->created_by)->first();

    $participantCount = $this->service->participantCount($id);
    $voters = $activeReview
      ? $this->service->votersForProblem((int) $activeReview->id)
      : [];
    $comments = $activeReview
      ? $this->service->commentsForProblem($id, (int) $activeReview->id)
      : [];

    return view('DosenProblemReview', [
      'project' => [
        'id' => $project->id,
        'name' => $project->title,
        'status' => $project->status,
        'group_name' => $project->group_name,
        'course_name' => $project->course_name,
        'deskripsi' => $parsed['deskripsi'],
        'creator_name' => $creator->full_name ?? $creator->name ?? '-',
      ],
      'activeReview' => $activeReview,
      'history' => $pending,
      'voters' => $voters,
      'comments' => $comments,
      'participantCount' => $participantCount,
    ]);
  }

  public function review(Request $request, int $id, int $problemId)
  {
    if (Auth::user()->role !== 'lecturer') {
      abort(403);
    }

    $validated = $request->validate([
      'action' => ['required', 'in:approve,reject'],
      'feedback' => ['nullable', 'string', 'max:2000'],
    ]);

    $this->service->lecturerReview(
      $id,
      $problemId,
      Auth::user(),
      $validated['action'],
      $validated['feedback'] ?? null
    );

    $message = $validated['action'] === 'approve'
      ? 'Masalah utama disetujui.'
      : 'Masalah dikembalikan ke mahasiswa untuk diperbaiki.';

    return redirect()
      ->route('dosen.problem-review', $id)
      ->with('success', $message);
  }
}
