<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BuatProjekController extends Controller
{
    public function __construct(
        private readonly ProjectWorkspaceService $workspace
    ) {}

    public function index()
    {
        return view('BuatProjek', [
            'isEdit' => false,
            'project' => null,
        ]);
    }

    public function edit(int $id)
    {
        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::userCanAccess(Auth::user(), $project)) {
            abort(403);
        }

        if ($project->status !== 'draft') {
            return redirect()
                ->route('problem-identification', $project->id)
                ->with('info', 'Hanya proyek berstatus draft yang dapat diedit.');
        }

        if ((int) $project->created_by !== (int) Auth::id()) {
            return redirect()
                ->route('problem-identification', $project->id)
                ->with('error', 'Hanya pembuat proyek yang dapat mengedit draft.');
        }

        $parsed = ProjectAccess::parseProjectDescription($project->description);
        $memberEmails = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $project->id)
            ->where('project_members.user_id', '!=', $project->created_by)
            ->pluck('users.email')
            ->implode(', ');

        return view('BuatProjek', [
            'isEdit' => true,
            'project' => $project,
            'formDefaults' => [
                'judul' => $project->title,
                'group_name' => $project->group_name,
                'course_name' => $project->course_name,
                'masalah' => $parsed['masalah'],
                'deskripsi' => $parsed['deskripsi'],
                'lecturer_name' => $project->lecturer_name,
                'lecturer_email' => $project->lecturer_email,
                'planned_months' => $project->planned_months ?? 6,
                'member_emails' => $memberEmails,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProjectForm($request);

        try {
            $description = $this->buildDescription($validated, $request);
            $memberEmails = $this->parseMemberEmails($validated['member_emails'] ?? '');
            $months = (int) $validated['planned_months'];

            $project = Project::create([
                'name' => $validated['judul'],
                'group_name' => $validated['group_name'],
                'course_name' => $validated['course_name'],
                'description' => $description,
                'status' => 'draft',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths($months)->toDateString(),
                'planned_months' => $months,
                'created_by' => Auth::id(),
                'lecturer_email' => strtolower($validated['lecturer_email']),
                'lecturer_name' => $validated['lecturer_name'],
            ]);

            $this->workspace->initialize(
                $project,
                Auth::user(),
                $validated['lecturer_email'],
                $memberEmails
            );

            return $this->redirectAfterSave($project, $validated['action'], true);
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors([
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Gagal menyimpan proyek. Periksa koneksi database dan struktur tabel.',
            ])->withInput();
        }
    }

    public function update(Request $request, int $id)
    {
        $project = Project::query()->findOrFail($id);

        if ((int) $project->created_by !== (int) Auth::id() || $project->status !== 'draft') {
            abort(403);
        }

        $validated = $this->validateProjectForm($request);
        $months = (int) $validated['planned_months'];

        $project->update([
            'name' => $validated['judul'],
            'group_name' => $validated['group_name'],
            'course_name' => $validated['course_name'],
            'description' => $this->buildDescription($validated, $request, $project->description),
            'end_date' => now()->addMonths($months)->toDateString(),
            'planned_months' => $months,
            'lecturer_email' => strtolower($validated['lecturer_email']),
            'lecturer_name' => $validated['lecturer_name'],
        ]);

        return $this->redirectAfterSave($project->fresh(), $validated['action'], false);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProjectForm(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'group_name' => ['required', 'string', 'max:150'],
            'course_name' => ['required', 'string', 'max:150'],
            'masalah' => ['required', 'string'],
            'deskripsi' => ['required', 'string'],
            'lecturer_name' => ['required', 'string', 'max:255'],
            'lecturer_email' => ['required', 'email', 'max:255'],
            'planned_months' => ['required', 'integer', 'min:1', 'max:36'],
            'member_emails' => ['nullable', 'string'],
            'action' => ['required', 'in:draft,submit'],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildDescription(array $validated, Request $request, ?string $existing = null): string
    {
        $description = trim($validated['deskripsi']);
        $description .= "\n\n--- Masalah utama ---\n".trim($validated['masalah']);

        if ($request->hasFile('lampiran')) {
            $files = $request->file('lampiran');
            if (! empty($files)) {
                $path = $files[0]->store('project_logos', 'public');
                $description .= "\n\n[Lampiran: ".Storage::disk('public')->url($path).']';
            }
        } elseif ($existing && preg_match('/\n\n\[Lampiran: [^\]]+\]/', $existing, $m)) {
            $description .= "\n\n".$m[0];
        }

        return $description;
    }

    private function redirectAfterSave(Project $project, string $action, bool $isNew): \Illuminate\Http\RedirectResponse
    {
        if ($action === 'submit') {
            $project = $project->fresh();

            if ($project->status !== 'draft') {
                return redirect()
                    ->route('problem-identification', $project->id)
                    ->with('info', 'Proyek sudah diajukan dan berstatus In Review.');
            }

            $this->workspace->submitToLecturer($project);

            return redirect()
                ->route('problem-identification', $project->id)
                ->with('success', 'Proyek berhasil diajukan ke dosen. Status proyek: In Review.');
        }

        $message = $isNew
            ? 'Proyek disimpan sebagai draft. Anda dapat melanjutkan pengisian atau mengajukan ke dosen nanti.'
            : 'Perubahan draft berhasil disimpan.';

        return redirect()
            ->route('problem-identification', $project->id)
            ->with('success', $message);
    }

    /**
     * @return list<string>
     */
    private function parseMemberEmails(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_values($parts) : [];
    }
}
