<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectChatController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);
        $project = Project::query()->find($id);

        if (! $selected || ! $project || ! $this->canAccess($project)) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $canManage = (int) $project->created_by === (int) Auth::id();
        $creatorId = (int) $project->created_by;
        $currentId = (int) Auth::id();

        $members = ProjectAccess::teamMembersForProject((int) $id);

        $messages = ProjectMessage::query()
            ->with('user')
            ->where('project_id', $project->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProjectMessage $m) => [
                'id' => (int) $m->id,
                'author' => $m->user ? $this->displayName($m->user) : 'Pengguna',
                'role' => $m->user_id === $creatorId ? 'PM' : 'Anggota',
                'text' => $m->body,
                'time' => optional($m->created_at)->format('d M · H:i'),
                'edited' => $m->updated_at && $m->created_at && $m->updated_at->gt($m->created_at),
                'mine' => $m->user_id === $currentId,
                'can_edit' => $m->user_id === $currentId,
                'can_delete' => $m->user_id === $currentId || $canManage,
                'attachment_url' => $m->attachmentUrl(),
                'attachment_name' => $m->attachment_name,
                'is_image' => $m->isImage(),
            ]);

        return view('ProjectChat', [
            'id' => (int) $id,
            'namaProjek' => $selected['name'],
            'members' => $members,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, $id)
    {
        $project = Project::query()->find($id);

        if (! $project || ! $this->canAccess($project)) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip',
            ],
        ]);

        $message = trim((string) ($validated['message'] ?? ''));

        if ($message === '' && ! $request->hasFile('attachment')) {
            return back()->with('error', 'Tulis pesan atau lampirkan file terlebih dahulu.');
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('project_chat/'.$project->id, 'public');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }

        ProjectMessage::query()->create([
            'project_id' => $project->id,
            'user_id' => (int) Auth::id(),
            'body' => $message,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);

        return redirect()->route('project-chat', $project->id);
    }

    public function updateMessage(Request $request, $id, $messageId)
    {
        $project = Project::query()->find($id);

        if (! $project || ! $this->canAccess($project)) {
            abort(403);
        }

        $message = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->findOrFail($messageId);

        if ((int) $message->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message->update(['body' => trim($validated['message'])]);

        return redirect()->route('project-chat', $project->id);
    }

    public function deleteMessage($id, $messageId)
    {
        $project = Project::query()->find($id);

        if (! $project || ! $this->canAccess($project)) {
            abort(403);
        }

        $message = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->findOrFail($messageId);

        $isManager = (int) $project->created_by === (int) Auth::id();

        if ((int) $message->user_id !== (int) Auth::id() && ! $isManager) {
            abort(403);
        }

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();

        return redirect()->route('project-chat', $project->id);
    }

    protected function canAccess(Project $project): bool
    {
        return ProjectAccess::userCanAccess((int) Auth::id(), $project);
    }

    protected function displayName(\App\Models\User $user): string
    {
        $name = trim($user->displayName());

        return $name !== '' ? $name : (string) $user->email;
    }
}
