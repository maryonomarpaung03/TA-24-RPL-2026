<?php

namespace App\Http\Controllers;

use App\Models\ProjectBoard;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use App\Models\TaskComment;
use App\Models\Task;
class PelaksanaanController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (!$selected) {

            return redirect()
                ->route('my-project')
                ->with('error', 'Project tidak ditemukan.');

        }

        $boards = ProjectBoard::with('tasks.comments')
            ->where('project_id', $id)
            ->orderBy('position')
            ->get();
        // dd($boards);
        return view('Pelaksanaan', [

            'user' => PjblContext::viewer(),

            'namaProjek' => $selected['name'],

            'id' => $id,

            'boards' => $boards,

            'teamInitials' => PjblContext::memberInitials($id),
            'allBoards' => $boards,

        ]);
    }
    public function moveTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'board_id' => 'required|exists:project_boards,id',
        ]);

        $task = Task::findOrFail($request->task_id);

        $task->board_id = $request->board_id;
        $task->save();

        return response()->json([
            'success' => true
        ]);
    }
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'name' => 'required|max:100'
        ]);

        ProjectBoard::create([
            'project_id' => $projectId,
            'name' => $request->name,
            'position' => ProjectBoard::where('project_id', $projectId)->count() + 1,
            'is_completed' => false
        ]);


        return redirect()
            ->route('pelaksanaan', $projectId)
            ->with('success', 'Board berhasil ditambahkan.');
    }
    public function comment(Request $request, $taskId)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $task = Task::findOrFail($taskId);

        TaskComment::create([
            'task_id' => $task->id,
            'comment' => $request->comment,
        ]);

        return redirect()
            ->route('pelaksanaan', $task->project_id)
            ->with('success', 'Komentar berhasil ditambahkan.');
    }
    public function updateTask(Request $request, $taskId)
    {
        $request->validate([
            'task_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'link' => 'nullable|url',
            'board_id' => 'required|exists:project_boards,id',
            'status' => 'required|in:pending,in_progress,completed',
            'progress_percent' => 'required|integer|min:0|max:100',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::findOrFail($taskId);

        $task->update([
            'board_id' => $request->board_id,
            'task_title' => $request->task_title,
            'description' => $request->description,
            'link' => $request->link,
            'priority' => $request->priority,
            'status' => $request->status,
            'progress_percent' => $request->progress_percent,
            'due_date' => $request->due_date,
        ]);

        return redirect()
            ->route('pelaksanaan', $task->project_id)
            ->with('success', 'Task berhasil diperbarui.');
    }
}