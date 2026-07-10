<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ProjectBoard;
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