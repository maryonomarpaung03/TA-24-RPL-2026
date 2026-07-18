<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStageGate;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DosenStageGateController extends Controller
{
    public function show(int $id, string $stage, StageProgressService $stages)
    {
        $project = $this->project($id);
        abort_unless(in_array($stage, StageProgressService::GATED_STAGES, true), 404);
        $gate = ProjectStageGate::query()->where('project_id', $id)->where('stage', $stage)->first();
        return view('DosenStageGate', compact('project', 'stage', 'gate') + ['summary' => $stages->summaryFor($id, $stage)]);
    }

    public function review(Request $request, int $id, string $stage, StageProgressService $stages)
    {
        $project = $this->project($id);
        $data = $request->validate(['action' => 'required|in:approve,revision', 'lecturer_note' => 'nullable|string|max:2000']);
        if ($data['action'] === 'revision' && blank($data['lecturer_note'])) return back()->with('error', 'Catatan revisi wajib diisi.');
        $stages->reviewGate($project, $stage, (int) Auth::id(), $data['action'] === 'approve', $data['lecturer_note'] ?? null);
        return back()->with('success', $data['action'] === 'approve' ? 'Tahap disetujui dan tahap berikutnya terbuka.' : 'Revisi dikirim ke mahasiswa.');
    }

    private function project(int $id): Project
    {
        abort_unless(Auth::user()?->role === 'lecturer', 403);
        $project = Project::findOrFail($id);
        abort_unless(ProjectAccess::lecturerCanView(Auth::user(), $project), 403);
        return $project;
    }
}
