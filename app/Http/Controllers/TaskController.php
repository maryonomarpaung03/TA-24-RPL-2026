<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectBoard;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, $board)
    {
        $request->validate([

            'title' => 'required|max:255',

        ]);

        $projectBoard =
            ProjectBoard::findOrFail($board);

        $status = Project::query()->where('id', $projectBoard->project_id)->value('status');

        if (ProjectAccess::isFinalized($status)) {
            return back()->with(
                'error',
                'Proyek sudah difinalisasi dan sedang dinilai dosen. Papan tugas terkunci.'
            );
        }

        Task::create([
            'project_id' => $projectBoard->project_id,
            'board_id' => $board,
            'milestone_id' => 2,
            'task_title' => $request->title,
            'priority' => 'medium',
            'status' => 'pending',
            'progress_percent' => 0,
        ]);

        return back()->with(
            'success',
            'Task berhasil ditambahkan.'
        );
    }

}