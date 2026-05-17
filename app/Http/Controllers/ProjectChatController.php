<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectChatController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $user = PjblContext::viewer();
        $members = PjblContext::memberNames((int) $id);
        $messages = session('project_chat_'.$id, []);

        return view('ProjectChat', [
            'id' => (int) $id,
            'user' => $user,
            'namaProjek' => $selected['name'],
            'members' => $members,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, $id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $viewer = PjblContext::viewer();
        $messages = session('project_chat_'.$id, []);
        $messages[] = [
            'author' => $viewer['name'],
            'text' => trim($validated['message']),
            'time' => now()->format('H:i'),
        ];

        session(['project_chat_'.$id => $messages]);

        return redirect()->route('project-chat', $id);
    }
}
