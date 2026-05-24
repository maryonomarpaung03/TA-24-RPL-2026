<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProblemIdentificationService;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProblemIdentificationController extends Controller
{
  public function __construct(
    private ProblemIdentificationService $service
  ) {}

  public function store(Request $request, int $id): JsonResponse
  {
    $user = Auth::user();

    $validated = $request->validate([
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'category' => ['required', 'in:' . implode(',', ProblemIdentificationService::CATEGORIES)],
      'priority' => ['required', 'in:' . implode(',', ProblemIdentificationService::PRIORITIES)],
      'attachment' => ['nullable', 'string'],
    ]);

    $card = $this->service->createIdea($id, $user, $validated);

    return response()->json([
      'success' => true,
      'card' => $card,
    ]);
  }

  public function proposeForVoting(Request $request, int $id): JsonResponse
  {
    $request->validate(['problem_id' => ['required', 'integer']]);

    $card = $this->service->proposeForVoting(
      $id,
      (int) $request->problem_id,
      Auth::user()
    );

    $board = $this->service->buildBoard($id, (int) Auth::id());

    return response()->json([
      'success' => true,
      'card' => $card,
      'board' => $board,
    ]);
  }

  public function vote(Request $request, int $id): JsonResponse
  {
    $request->validate(['problem_id' => ['required', 'integer']]);

    $result = $this->service->castVote(
      $id,
      (int) $request->problem_id,
      Auth::user()
    );

    return response()->json([
      'success' => true,
      'board' => $result['board'],
      'auto_submitted' => $result['auto_submitted'],
      'voters_count' => $this->service->distinctVoterCount($id),
    ]);
  }

  public function submitToLecturer(int $id): JsonResponse
  {
    $card = $this->service->submitToLecturer($id, Auth::user());
    $board = $this->service->buildBoard($id, (int) Auth::id());

    return response()->json([
      'success' => true,
      'card' => $card,
      'board' => $board,
    ]);
  }

  public function comment(Request $request, int $id): JsonResponse
  {
    $validated = $request->validate([
      'problem_id' => ['required', 'integer'],
      'message' => ['required', 'string', 'max:2000'],
      'parent_id' => ['nullable', 'integer'],
    ]);

    $result = $this->service->addDiscussionComment(
      $id,
      Auth::user(),
      (int) $validated['problem_id'],
      $validated['message'],
      isset($validated['parent_id']) ? (int) $validated['parent_id'] : null
    );

    return response()->json([
      'success' => true,
      'comments' => $result['comments'],
    ]);
  }

  public function resubmit(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'problem_id' => ['required', 'integer'],
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'category' => ['required', 'in:' . implode(',', ProblemIdentificationService::CATEGORIES)],
      'priority' => ['required', 'in:' . implode(',', ProblemIdentificationService::PRIORITIES)],
      'attachment' => ['nullable', 'string'],
    ]);

    $card = $this->service->resubmit(
      $id,
      (int) $request->problem_id,
      Auth::user(),
      $request->only(['title', 'description', 'category', 'priority', 'attachment'])
    );

    $board = $this->service->buildBoard($id, (int) Auth::id());

    return response()->json([
      'success' => true,
      'card' => $card,
      'board' => $board,
    ]);
  }

  /**
   * Data untuk halaman problem identification (dipanggil dari DashboardController).
   *
   * @return array<string, mixed>
   */
  public static function pageData(int $projectId): array
  {
    $user = Auth::user();
    $service = app(ProblemIdentificationService::class);

    $selectedProject = ProjectCatalog::find($projectId);
    $teamMembers = ProjectAccess::teamMembersForProject($projectId);
    $participantCount = $service->participantCount($projectId);
    $problemBoard = $service->buildBoard($projectId, (int) $user->id);

    $project = Project::query()->find($projectId);
    $isPm = $project && (int) $project->created_by === (int) $user->id;

    $votersCount = $service->distinctVoterCount($projectId);

    $problemComments = $service->discussionThreadForProject($projectId);

    return [
      'selected_project' => $selectedProject,
      'problemBoard' => $problemBoard,
      'problemComments' => $problemComments,
      'teamMembers' => $teamMembers,
      'participantCount' => $participantCount,
      'votersCount' => $votersCount,
      'isPm' => $isPm,
      'votingOpen' => count($problemBoard['voting']) > 0,
      'currentUserId' => (int) $user->id,
    ];
  }
}
