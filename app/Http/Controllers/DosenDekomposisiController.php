<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenDekomposisiController extends Controller
{
    public function show($id)
    {
        $user = Auth::user();

        if ($user?->role !== 'lecturer') {
            abort(403);
        }

        $project = Project::query()->find($id);

        if (! $project) {
            return redirect()->route('dosen.proyek-mahasiswa')->with('error', 'Proyek tidak ditemukan.');
        }

        $submissions = DB::table('decomposition_submissions')
            ->join('users', 'decomposition_submissions.submitted_by', '=', 'users.id')
            ->where('decomposition_submissions.project_id', $id)
            ->orderByDesc('decomposition_submissions.created_at')
            ->select(
                'decomposition_submissions.*',
                'users.full_name as submitter_name'
            )
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'submitter'   => $s->submitter_name ?? 'Mahasiswa',
                'submitted_at' => $s->created_at,
                'status'      => $s->status,
                'nodes'       => json_decode($s->nodes_snapshot, true) ?? [],
                'connections' => json_decode($s->connections_snapshot, true) ?? [],
                'comments'    => json_decode($s->comments_snapshot, true) ?? [],
            ])
            ->toArray();

        $displayName = $user->full_name ?? $user->name ?? 'Dosen';

        return view('DosenDekomposisi', [
            'project'     => $project,
            'submissions' => $submissions,
            'user'        => [
                'name'     => $displayName,
                'initials' => ProjectAccess::initialsFromName($displayName),
            ],
        ]);
    }
}

