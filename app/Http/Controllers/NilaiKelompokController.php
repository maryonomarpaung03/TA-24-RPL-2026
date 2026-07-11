<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use App\Support\PjblContext;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NilaiKelompokController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $members = ProjectAccess::teamMembersForProject((int) $id);

        return view('NilaiKelompok', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'groupData' => [
                'name' => $selected['group_name'] ?? '-',
                'project' => $selected['name'],
                'members' => array_map(fn (array $m) => [
                    'name' => $m['name'],
                    'initial' => $m['initials'],
                    'role' => $m['role'],
                ], $members),
            ],
            'anggota' => array_column($members, 'name'),
            'memberList' => $this->evaluations->members((int) $id),
            'existing' => $this->evaluations->peerEvaluation((int) $id, (int) Auth::id()),
            'peer' => $this->evaluations->peerSummary((int) $id),
        ]);
    }

    public function store(Request $request, $id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $memberIds = array_map(fn ($m) => $m->id, $this->evaluations->members((int) $id));

        $validated = $request->validate([
            'group_score' => 'required|integer|min:0|max:100',
            'members' => 'required|array',
            'members.*' => 'required|integer|min:0|max:100',
            'reflection' => 'nullable|string|max:500',
        ]);

        $scores = [];

        foreach ($validated['members'] as $memberId => $score) {
            if (in_array((int) $memberId, $memberIds, true)) {
                $scores[(int) $memberId] = (int) $score;
            }
        }

        $this->evaluations->savePeerEvaluation(
            (int) $id,
            (int) Auth::id(),
            (int) $validated['group_score'],
            $scores,
            $validated['reflection'] ?? null
        );

        return redirect()
            ->route('penilaian-kelompok', $id)
            ->with('success', 'Penilaian kelompok Anda berhasil disimpan.');
    }
}
