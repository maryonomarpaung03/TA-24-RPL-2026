<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\FinalizationService;
use App\Services\ProjectTaskService;
use App\Services\StageProgressService;
use App\Support\KanbanFilter;
use App\Support\PjblContext;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use App\Support\TaskFilter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Papan Pelaksanaan mahasiswa.
 *
 * Tugas hanya lahir di Penyusunan: di sini tim tidak bisa menambah, mengubah,
 * atau menghapus tugas — hanya memindahkannya antar kolom dan mengumpulkan
 * hasilnya. Papan dibaca dari tasks.status (sama seperti papan dosen), jadi
 * seluruh tugas Penyusunan langsung muncul begitu tahapan itu difinalisasi.
 */
class PelaksanaanController extends Controller
{
    public function __construct(
        private readonly FinalizationService $finalization,
        private readonly ProjectTaskService $tasks,
        private readonly StageProgressService $stages,
    ) {}

    public function index(Request $request, $id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Project tidak ditemukan.');
        }

        $kanban = $this->tasks->kanbanForProject((int) $id);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'pj' => (string) $request->query('pj', ''),
            'prioritas' => (string) $request->query('prioritas', ''),
            'tenggat' => (string) $request->query('tenggat', ''),
        ];

        [$kanbanFiltered, $shownTasks, $totalTasks] = KanbanFilter::apply($kanban, $filters);

        // Papan terkunci bila proyek sudah difinalisasi ke dosen, atau bila tahap
        // Pelaksanaan sendiri sudah difinalisasi (middleware stage.waterfall juga
        // menolak POST-nya; ini yang menyembunyikan tombolnya).
        $locked = ProjectAccess::isFinalized($selected['status'] ?? null)
            || $this->stages->isFinalized((int) $id, StageProgressService::EXECUTION);

        return view('Pelaksanaan', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => $id,
            'kanban' => $kanbanFiltered,
            'columns' => $this->tasks->columnsForProject((int) $id),
            'progress' => $this->tasks->progressForProject((int) $id),
            'currentUserId' => (int) Auth::id(),

            'filterState' => $filters,
            'totalTasks' => $totalTasks,
            'shownTasks' => $shownTasks,
            'pjOptions' => TaskFilter::assigneeOptions(KanbanFilter::flatten($kanban)),
            'prioritasOptions' => KanbanFilter::PRIORITY_OPTIONS,
            'tenggatOptions' => KanbanFilter::DEADLINE_OPTIONS,
            'colorOptions' => ProjectTaskService::COLUMN_COLORS,

            // Finalisasi proyek
            'projectStatus' => $selected['status'] ?? null,
            'locked' => $locked,
            'lastSubmission' => $this->finalization->latestSubmission((int) $id),
        ]);
    }

    /**
     * Pindahkan tugas antar kolom. Bisa tertahan bila kolom tujuan menuntut
     * checklist atau persetujuan dosen.
     */
    public function pindahTugas(Request $request, $id, int $taskId)
    {
        $request->validate([
            'column_key' => 'required|string',
            'checklist_confirmed' => 'nullable|boolean',
        ]);

        if ($locked = $this->ensureNotLocked((int) $id)) {
            return $locked;
        }

        try {
            $result = $this->tasks->moveTask(
                (int) $id,
                $taskId,
                (string) $request->input('column_key'),
                (int) Auth::id(),
                $request->boolean('checklist_confirmed'),
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', $result['pending']
            ? 'Tugas diajukan ke dosen dan menunggu persetujuan untuk berpindah kolom.'
            : 'Tugas berhasil dipindahkan.');
    }

    /**
     * Pengumpulan hasil tugas: berupa link, atau berkas (foto/dokumen) yang
     * diunggah. Judul, deskripsi, tenggat, dan penanggung jawab tidak bisa
     * diubah di sini — itu milik Penyusunan.
     */
    public function submitTugas(Request $request, $id, int $taskId)
    {
        if ($locked = $this->ensureNotLocked((int) $id)) {
            return $locked;
        }

        $request->validate([
            'submission_type' => 'required|in:link,file',
            'link' => 'nullable|url|required_if:submission_type,link',
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip',
            ],
        ], [
            'link.required_if' => 'Link tugas wajib diisi bila pengumpulan berupa link.',
            'attachment.mimes' => 'Berkas harus berupa foto (jpg/png/webp/gif) atau dokumen (pdf/doc/xls/ppt/txt/zip).',
            'attachment.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $task = Task::query()
            ->where('id', $taskId)
            ->where('project_id', $id)
            ->firstOrFail();

        $submission = $this->submissionPayload($request, $task);

        // Pengumpulan diganti setelah dosen mereview: review lama tidak lagi berlaku.
        $changed = ($submission['link'] ?? null) !== $task->link
            || ($submission['attachment_path'] ?? $task->attachment_path) !== $task->attachment_path;

        $task->update($submission + ($changed ? ['reviewed_at' => null, 'reviewed_by' => null] : []));

        return back()->with('success', 'Hasil tugas berhasil dikumpulkan.');
    }

    public function tambahKolom(Request $request, $id)
    {
        $request->validate($this->columnRules());

        if ($locked = $this->ensureNotLocked((int) $id)) {
            return $locked;
        }

        try {
            $this->tasks->createColumn(
                (int) $id,
                (string) $request->input('label'),
                (string) $request->input('color'),
                $this->columnConfig($request),
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom papan berhasil ditambahkan.');
    }

    public function ubahKolom(Request $request, $id, int $columnId)
    {
        $request->validate($this->columnRules());

        if ($locked = $this->ensureNotLocked((int) $id)) {
            return $locked;
        }

        try {
            $this->tasks->updateColumn(
                (int) $id,
                $columnId,
                (string) $request->input('label'),
                (string) $request->input('color'),
                $this->columnConfig($request),
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom papan berhasil diperbarui.');
    }

    public function hapusKolom(Request $request, $id, int $columnId)
    {
        if ($locked = $this->ensureNotLocked((int) $id)) {
            return $locked;
        }

        try {
            $this->tasks->deleteColumn((int) $id, $columnId);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Kolom dihapus. Tugas di dalamnya dipindahkan ke kolom tersisa.');
    }

    /**
     * @return array<string, mixed>
     */
    private function columnRules(): array
    {
        return [
            'label' => 'required|string|max:60',
            'color' => 'required|string|in:'.implode(',', ProjectTaskService::COLUMN_COLORS),
            'description' => 'nullable|string|max:255',
            'is_done' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'checklist' => 'nullable|array|max:15',
            'checklist.*' => 'nullable|string|max:120',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function columnConfig(Request $request): array
    {
        return [
            'description' => $request->input('description'),
            'is_done' => $request->boolean('is_done'),
            'requires_approval' => $request->boolean('requires_approval'),
            'checklist' => (array) $request->input('checklist', []),
        ];
    }

    /**
     * Berkas lama dihapus saat diganti atau saat beralih ke link.
     *
     * @return array<string, mixed>
     */
    private function submissionPayload(Request $request, Task $task): array
    {
        if ($request->input('submission_type') === 'link') {
            $this->deleteAttachment($task);

            return [
                'submission_type' => 'link',
                'link' => $request->input('link'),
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
            ];
        }

        // Tipe file tanpa unggahan baru: pertahankan berkas yang sudah ada.
        if (! $request->hasFile('attachment')) {
            return ['submission_type' => 'file', 'link' => null];
        }

        $this->deleteAttachment($task);

        $file = $request->file('attachment');

        return [
            'submission_type' => 'file',
            'link' => null,
            'attachment_path' => $file->store('task_submissions/'.$task->project_id, 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
        ];
    }

    private function deleteAttachment(Task $task): void
    {
        if ($task->attachment_path) {
            Storage::disk('public')->delete($task->attachment_path);
        }
    }

    /**
     * Papan tugas terkunci setelah tim mengirim finalisasi proyek ke dosen.
     * Finalisasi tahap Pelaksanaan sendiri sudah ditangani stage.waterfall.
     */
    private function ensureNotLocked(int $projectId): ?RedirectResponse
    {
        $status = Project::query()->where('id', $projectId)->value('status');

        if (! ProjectAccess::isFinalized($status)) {
            return null;
        }

        return redirect()
            ->route('pelaksanaan', $projectId)
            ->with('error', 'Proyek sudah difinalisasi dan sedang dinilai dosen. Papan tugas terkunci.');
    }
}
