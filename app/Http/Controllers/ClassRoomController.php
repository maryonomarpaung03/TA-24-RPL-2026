<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\ClassMember;
use App\Models\ClassMessage;
use App\Models\Project;
use App\Models\User;
use App\Support\ClassActivity;
use App\Support\ProjectAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClassRoomController extends Controller
{
    /**
     * Tampilan ruang kelas: info kelas, daftar anggota, dan chat room.
     */
    public function show($id)
    {
        $academicClass = AcademicClass::query()
            ->with('lecturer')
            ->find($id);

        if (! $academicClass || ! $this->canAccess($academicClass)) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Kelas tidak ditemukan atau Anda belum tergabung di kelas ini.');
        }

        $canManage = $this->isOwner($academicClass);

        // Dosen pengampu + semua mahasiswa yang tergabung.
        $students = $academicClass->members()->orderBy('full_name')->get();

        $participants = collect();
        if ($academicClass->lecturer) {
            $participants->push([
                'id' => (int) $academicClass->lecturer_id,
                'name' => $this->displayName($academicClass->lecturer),
                'email' => $academicClass->lecturer->email,
                'role' => 'Dosen',
                'removable' => false,
            ]);
        }
        foreach ($students as $student) {
            $participants->push([
                'id' => (int) $student->id,
                'name' => $this->displayName($student),
                'email' => $student->email,
                'role' => 'Mahasiswa',
                'removable' => $canManage,
            ]);
        }

        $currentId = (int) Auth::id();

        $messages = ClassMessage::query()
            ->with('user')
            ->where('academic_class_id', $academicClass->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ClassMessage $m) => [
                'id' => (int) $m->id,
                'author' => $m->user ? $this->displayName($m->user) : 'Pengguna',
                'role' => $m->user_id === (int) $academicClass->lecturer_id ? 'Dosen' : 'Mahasiswa',
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

        // Hitung yang baru sejak terakhir dibuka, lalu tandai kelas sudah dibaca.
        $unread = ClassActivity::unreadForClass($academicClass, Auth::user());
        ClassActivity::markRead((int) Auth::id(), (int) $academicClass->id);

        return view('KelasDetail', [
            'academicClass' => $academicClass,
            'participants' => $participants,
            'messages' => $messages,
            'studentCount' => $students->count(),
            'canManage' => $canManage,
            'projects' => $this->classProjects($academicClass),
            'unread' => $unread,
        ]);
    }

    /**
     * Proyek yang terhubung ke kelas ini.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function classProjects(AcademicClass $academicClass)
    {
        if (! Schema::hasColumn('projects', 'academic_class_id')) {
            return collect();
        }

        $statusMap = [
            'draft' => 'Draft',
            'pending_approval' => 'In Review',
            'pending_revision' => 'Review Perubahan',
            'active' => 'In Progress',
            'completed' => 'Done',
            'rejected' => 'Rejected',
            'archived' => 'Archived',
        ];

        $isLecturer = Auth::user()->role === 'lecturer';

        return Project::query()
            ->where('academic_class_id', $academicClass->id)
            // Proyek draft hanya tampil untuk mahasiswa; dosen tidak melihat draft.
            ->when($isLecturer, fn ($q) => $q->where('status', '!=', 'draft'))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->title,
                'status' => $statusMap[$project->status] ?? 'Planning',
                'db_status' => $project->status,
                'description' => ProjectAccess::shortDescription($project->description),
                'created_at' => Carbon::parse($project->created_at)->format('d/m/Y'),
                'members' => ProjectAccess::memberInitials($project->id),
                'group_name' => $project->group_name,
                'url' => $this->projectDetailUrl($project, $isLecturer),
            ]);
    }

    /**
     * Tautan detail proyek sesuai peran & status.
     */
    protected function projectDetailUrl(Project $project, bool $isLecturer): string
    {
        if (! $isLecturer) {
            // Mahasiswa: langsung ke workspace/detail proyek.
            return route('problem-identification', $project->id);
        }

        // Dosen: ke halaman detail proyek dosen sesuai status.
        return in_array($project->status, ['active', 'completed'], true)
            ? route('dosen.proyek-mahasiswa.show', $project->id)
            : route('dosen.persetujuan.show', $project->id);
    }

    /**
     * Kirim pesan ke chat room kelas.
     */
    public function send(Request $request, $id)
    {
        $academicClass = AcademicClass::query()->find($id);

        if (! $academicClass || ! $this->canAccess($academicClass)) {
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
            $attachmentPath = $file->store('class_chat/'.$academicClass->id, 'public');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }

        ClassMessage::query()->create([
            'academic_class_id' => $academicClass->id,
            'user_id' => (int) Auth::id(),
            'body' => $message,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);

        return redirect()->route('classes.show', $academicClass->id);
    }

    /**
     * Edit pesan (hanya pemilik pesan).
     */
    public function updateMessage(Request $request, $id, $messageId)
    {
        $academicClass = AcademicClass::query()->find($id);

        if (! $academicClass || ! $this->canAccess($academicClass)) {
            abort(403);
        }

        $message = ClassMessage::query()
            ->where('academic_class_id', $academicClass->id)
            ->findOrFail($messageId);

        if ((int) $message->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message->update(['body' => trim($validated['message'])]);

        return redirect()->route('classes.show', $academicClass->id);
    }

    /**
     * Hapus pesan (pemilik pesan atau dosen pengampu).
     */
    public function deleteMessage($id, $messageId)
    {
        $academicClass = AcademicClass::query()->find($id);

        if (! $academicClass || ! $this->canAccess($academicClass)) {
            abort(403);
        }

        $message = ClassMessage::query()
            ->where('academic_class_id', $academicClass->id)
            ->findOrFail($messageId);

        if ((int) $message->user_id !== (int) Auth::id() && ! $this->isOwner($academicClass)) {
            abort(403);
        }

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();

        return redirect()->route('classes.show', $academicClass->id);
    }

    /**
     * Tambah anggota kelas (khusus dosen pengampu).
     */
    public function addMember(Request $request, $id)
    {
        $academicClass = AcademicClass::query()->findOrFail($id);

        if (! $this->isOwner($academicClass)) {
            abort(403);
        }

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($validated['identifier']);

        $student = User::query()
            ->where('role', 'student')
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)->orWhere('nim', $identifier);
            })
            ->first();

        if (! $student) {
            return back()->with('error', 'Mahasiswa dengan email/NIM "'.$identifier.'" tidak ditemukan.');
        }

        $alreadyMember = ClassMember::query()
            ->where('academic_class_id', $academicClass->id)
            ->where('user_id', $student->id)
            ->exists();

        if ($alreadyMember) {
            return back()->with('info', $this->displayName($student).' sudah menjadi anggota kelas.');
        }

        if ($academicClass->isFull()) {
            return back()->with('error', 'Kelas sudah penuh.');
        }

        ClassMember::query()->create([
            'academic_class_id' => $academicClass->id,
            'user_id' => $student->id,
            'joined_at' => now(),
        ]);

        return back()->with('success', $this->displayName($student).' berhasil ditambahkan ke kelas.');
    }

    /**
     * Keluarkan anggota kelas (khusus dosen pengampu).
     */
    public function removeMember($id, $userId)
    {
        $academicClass = AcademicClass::query()->findOrFail($id);

        if (! $this->isOwner($academicClass)) {
            abort(403);
        }

        ClassMember::query()
            ->where('academic_class_id', $academicClass->id)
            ->where('user_id', (int) $userId)
            ->delete();

        return back()->with('success', 'Anggota dikeluarkan dari kelas.');
    }

    /**
     * Boleh diakses oleh dosen pengampu atau mahasiswa yang sudah tergabung.
     */
    protected function canAccess(AcademicClass $academicClass): bool
    {
        $userId = (int) Auth::id();

        if ((int) $academicClass->lecturer_id === $userId) {
            return true;
        }

        return $academicClass->members()->where('users.id', $userId)->exists();
    }

    /**
     * Apakah pengguna saat ini adalah dosen pengampu kelas.
     */
    protected function isOwner(AcademicClass $academicClass): bool
    {
        return (int) $academicClass->lecturer_id === (int) Auth::id();
    }

    protected function displayName(\App\Models\User $user): string
    {
        $name = trim($user->displayName());

        return $name !== '' ? $name : (string) $user->email;
    }
}
