<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskCommentController extends Controller
{
    /**
     * Simpan komentar pada sebuah tugas. Bisa dilakukan anggota proyek
     * (mahasiswa) maupun dosen pengampu selama proyek berjalan.
     */
    public function store(Request $request, $id, $taskId)
    {
        $request->validate([
            'komentar' => ['required', 'string', 'max:2000'],
        ]);

        $project = Project::query()->find($id);

        if (! $project) {
            return back()->with('error', 'Proyek tidak ditemukan.');
        }

        $user = Auth::user();
        $canComment = ProjectAccess::userCanAccess((int) $user->id, $project)
            || ProjectAccess::lecturerCanView($user, $project);

        if (! $canComment) {
            abort(403, 'Anda tidak memiliki akses untuk berkomentar di proyek ini.');
        }

        $taskExists = DB::table('tasks')
            ->where('id', $taskId)
            ->where('project_id', $project->id)
            ->exists();

        if (! $taskExists) {
            return back()->with('error', 'Tugas tidak ditemukan.');
        }

        DB::table('discussions')->insert([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'task_id' => (int) $taskId,
            'message' => trim($request->komentar),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Komentar berhasil dikirim.');
    }
}
