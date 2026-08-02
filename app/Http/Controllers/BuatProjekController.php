<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\AcademicClass;
use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuatProjekController extends Controller
{
    public function __construct(
        private readonly ProjectWorkspaceService $workspace
    ) {}

    public function index(Request $request)
    {
        $classContext = null;
        $formDefaults = [];

        $academicClass = $this->resolveClassContext($request->query('class'));

        if ($academicClass) {
            $classContext = [
                'id' => $academicClass->id,
                'name' => $academicClass->name,
            ];
            $formDefaults = [
                'course_name' => $academicClass->course_name,
                'lecturer_name' => $academicClass->lecturer
                    ? (trim($academicClass->lecturer->displayName()) ?: $academicClass->lecturer->email)
                    : '',
                'lecturer_email' => $academicClass->lecturer->email ?? '',
            ];
        }

        return view('BuatProjek', [
            'isEdit' => false,
            'project' => null,
            'attachmentMedia' => ProjectAccess::projectMediaPreview(null, null),
            'formDefaults' => $formDefaults,
            'classContext' => $classContext,
        ]);
    }

    /**
     * Ambil kelas jika pengguna berhak (dosen pengampu atau anggota).
     */
    private function resolveClassContext($classId): ?AcademicClass
    {
        if (! $classId) {
            return null;
        }

        $academicClass = AcademicClass::query()->with('lecturer')->find($classId);

        if (! $academicClass) {
            return null;
        }

        $userId = (int) Auth::id();

        if ((int) $academicClass->lecturer_id === $userId) {
            return $academicClass;
        }

        $isMember = $academicClass->members()->where('users.id', $userId)->exists();

        return $isMember ? $academicClass : null;
    }

    public function edit(int $id)
    {
        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::isProjectManager($project, (int) Auth::id())) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Hanya Project Manager yang dapat mengedit proyek ini.');
        }

        if (! in_array($project->status, ProjectAccess::editableStatuses(), true)) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek dengan status ini tidak dapat diedit.');
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
            'editMode' => in_array($project->status, ['draft'], true) ? 'draft' : 'revision',
            'attachmentMedia' => ProjectAccess::projectMediaPreview(
                $project->logo,
                $project->description
            ),
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
        $validated = $this->validateProjectForm($request, 'draft');
        
        try {
            $description = $this->buildDescription($validated);
            $logoPath = ProjectAccess::storeProjectAttachment($request);
            $memberEmails = $this->parseMemberEmails($validated['member_emails'] ?? '');
            $months = (int) $validated['planned_months'];

            $academicClass = $this->resolveClassContext($request->input('academic_class_id'));

            $project = Project::create([
                'name' => $validated['judul'],
                'title' =>$validated['judul'],
                'academic_class_id' => $academicClass?->id,
                'group_name' => $validated['group_name'],
                'course_name' => $validated['course_name'],
                'description' => $description,
                'logo' => $logoPath,
                'status' => 'draft',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths($months)->toDateString(),
                'planned_months' => $months,
                'created_by' => Auth::id(),
                'lecturer_email' => strtolower($validated['lecturer_email']),
                'lecturer_name' => $validated['lecturer_name'],
            ]);

            $skippedEmails = $this->workspace->initialize(
                $project,
                Auth::user(),
                $validated['lecturer_email'],
                $memberEmails
            );

            return $this->redirectAfterSave($project, $validated['action'], true, $skippedEmails);
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors([
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Gagal menyimpan proyek. Periksa koneksi database dan struktur tabel.',
            ])->withInput();
        }
    }

public function getProjectsByEmail(Request $request)
{
    // ambil user dari token API
    $user = $request->attributes->get('auth_user');


    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User token tidak ditemukan'
        ], 401);
    }


    // hanya mahasiswa
    if ($user->role !== 'student') {
        return response()->json([
            'success' => false,
            'message' => 'API ini hanya untuk mahasiswa'
        ], 403);
    }


    $projects = Project::where('created_by', $user->id)
        ->latest()
        ->get();


    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ],
        'data' => $projects
    ]);
}
public function storeAPI(Request $request)
{
    // user dari token
   $user = $request->attributes->get('auth_user');
    $validated = $this->validateProjectForm($request, 'draft');

    try {

        $description = $this->buildDescription($validated);

        $logoPath = ProjectAccess::storeProjectAttachment($request);

        $memberEmails = $this->parseMemberEmails(
            $validated['member_emails'] ?? ''
        );

        $months = (int) $validated['planned_months'];


        $academicClass = $this->resolveClassContext(
            $request->input('academic_class_id')
        );


        $project = Project::create([
            'name'              => $validated['judul'],
            'title'             => $validated['judul'],
            'academic_class_id' => $academicClass?->id,
            'group_name'        => $validated['group_name'],
            'course_name'       => $validated['course_name'],
            'description'       => $description,
            'logo'              => $logoPath,
            'status'            => 'pending_approval',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths($months)->toDateString(),
            'planned_months'    => $months,

            // dari token
            'created_by'        => $user->id,

            'lecturer_email'    => strtolower($validated['lecturer_email']),
            'lecturer_name'     => $validated['lecturer_name'],
        ]);


        $skippedEmails = $this->workspace->initialize(
            $project,
            $user,
            $validated['lecturer_email'],
            $memberEmails
        );


        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dibuat.',
            'data' => [
                'project' => $project,
                'created_by' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'skipped_emails' => $skippedEmails,
            ]
        ], 201);


    } catch (\Exception $e) {

        report($e);

        return response()->json([
            'success' => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'Gagal menyimpan proyek.',
        ], 500);
    }
}
// public function storeAPI(Request $request)
// {
// $email = strtolower($request->input('email'));
// $request->request->remove('email');

//     $validated = $this->validateProjectForm($request, 'draft');

//     try {
//         $user = User::where('email', $email)->firstOrFail();
//         // $user = User::where(
//         //     'email',
//         //     strtolower($validated['email'])
//         // )->firstOrFail();

//         $description = $this->buildDescription($validated);
//         $logoPath = ProjectAccess::storeProjectAttachment($request);
//         $memberEmails = $this->parseMemberEmails($validated['member_emails'] ?? '');
//         $months = (int) $validated['planned_months'];

//         $academicClass = $this->resolveClassContext(
//             $request->input('academic_class_id')
//         );

//         $project = Project::create([
//             'name'              => $validated['judul'],
//             'title'             => $validated['judul'],
//             'academic_class_id' => $academicClass?->id,
//             'group_name'        => $validated['group_name'],
//             'course_name'       => $validated['course_name'],
//             'description'       => $description,
//             'logo'              => $logoPath,
//             'status'            => 'pending_approval',
//             'start_date'        => now()->toDateString(),
//             'end_date'          => now()->addMonths($months)->toDateString(),
//             'planned_months'    => $months,
//             'created_by'        => $user->id,
//             'lecturer_email'    => strtolower($validated['lecturer_email']),
//             'lecturer_name'     => $validated['lecturer_name'],
//         ]);

//         $skippedEmails = $this->workspace->initialize(
//             $project,
//             $user,
//             $validated['lecturer_email'],
//             $memberEmails
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Project berhasil dibuat.',
//             'data' => [
//                 'project' => $project,
//                 'skipped_emails' => $skippedEmails,
//             ]
//         ], 201);

//     } catch (\Exception $e) {
//         report($e);

//         return response()->json([
//             'success' => false,
//             'message' => config('app.debug')
//                 ? $e->getMessage()
//                 : 'Gagal menyimpan proyek.',
//         ], 500);
//     }
// }

// public function updateAPI(Request $request)
// {
//     try {

//         $email = strtolower($request->input('email'));

//         $request->validate([
//             'id' => ['required', 'integer'],
//             'email' => ['required', 'email'],
//         ]);

//         $project = Project::findOrFail($request->id);

//         $user = User::where('email', $email)->firstOrFail();

//         if (! ProjectAccess::isProjectManager($project, $user->id)) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Hanya Project Manager yang dapat memperbarui proyek ini.'
//             ], 403);
//         }

//         if (! in_array(
//             $project->status,
//             ProjectAccess::editableStatuses(),
//             true
//         )) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Proyek dengan status ini tidak dapat diperbarui.'
//             ], 403);
//         }

//         $request->request->remove('email');
//         $request->request->remove('id');

//         $validated = $this->validateProjectForm(
//             $request,
//             $project->status
//         );

//         $months = (int) $validated['planned_months'];

//         $logoPath = ProjectAccess::storeProjectAttachment(
//             $request,
//             $project->logo
//         );

//         $project->update([
//             'name' => $validated['judul'],
//             'group_name' => $validated['group_name'],
//             'course_name' => $validated['course_name'],
//             'description' => $this->buildDescription($validated),
//             'logo' => $logoPath,
//             'end_date' => now()->addMonths($months)->toDateString(),
//             'planned_months' => $months,
//             'lecturer_email' => strtolower($validated['lecturer_email']),
//             'lecturer_name' => $validated['lecturer_name'],
//         ]);

//         $memberEmails = $this->parseMemberEmails(
//             $validated['member_emails'] ?? ''
//         );

//         $skippedEmails = $this->workspace->syncProjectMembers(
//             $project->fresh(),
//             $user,
//             $memberEmails
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Proyek berhasil diperbarui.',
//             'data' => [
//                 'project' => $project->fresh(),
//                 'skipped_emails' => $skippedEmails,
//             ]
//         ]);

//     } catch (\Exception $e) {

//         report($e);

//         return response()->json([
//             'success' => false,
//             'message' => config('app.debug')
//                 ? $e->getMessage()
//                 : 'Gagal memperbarui proyek.'
//         ], 500);
//     }
// }
public function updateAPI(Request $request)
{
    try {

        // user dari token API
        $user = $request->attributes->get('auth_user');


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User token tidak ditemukan'
            ], 401);
        }


        // khusus mahasiswa
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'API ini hanya untuk mahasiswa'
            ], 403);
        }


        $request->validate([
            'id' => ['required', 'integer'],
        ]);


        $project = Project::findOrFail($request->id);



        // cek apakah user token adalah Project Manager
        if (!ProjectAccess::isProjectManager(
            $project,
            $user->id
        )) {

            return response()->json([
                'success' => false,
                'message' => 'Hanya Project Manager yang dapat memperbarui proyek ini.'
            ], 403);
        }



        // cek status project
        if (!in_array(
            $project->status,
            ProjectAccess::editableStatuses(),
            true
        )) {

            return response()->json([
                'success' => false,
                'message' => 'Proyek dengan status ini tidak dapat diperbarui.'
            ], 403);

        }



        // hapus id agar tidak masuk validasi form
        $request->request->remove('id');


        $validated = $this->validateProjectForm(
            $request,
            $project->status
        );


        $months = (int) $validated['planned_months'];


        $logoPath = ProjectAccess::storeProjectAttachment(
            $request,
            $project->logo
        );



        $project->update([
            'name' => $validated['judul'],
            'group_name' => $validated['group_name'],
            'course_name' => $validated['course_name'],
            'description' => $this->buildDescription($validated),
            'logo' => $logoPath,
            'end_date' => now()->addMonths($months)->toDateString(),
            'planned_months' => $months,
            'lecturer_email' => strtolower($validated['lecturer_email']),
            'lecturer_name' => $validated['lecturer_name'],
        ]);



        $memberEmails = $this->parseMemberEmails(
            $validated['member_emails'] ?? ''
        );



        $skippedEmails = $this->workspace->syncProjectMembers(
            $project->fresh(),
            $user,
            $memberEmails
        );



        return response()->json([
            'success' => true,
            'message' => 'Proyek berhasil diperbarui.',
            'data' => [
                'project' => $project->fresh(),
                'updated_by' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'skipped_emails' => $skippedEmails,
            ]
        ]);


    } catch (\Exception $e) {

        report($e);

        return response()->json([
            'success' => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'Gagal memperbarui proyek.'
        ], 500);
    }
}
    public function update(Request $request, int $id)
    {
        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::isProjectManager($project, (int) Auth::id())) {
            abort(403, 'Hanya Project Manager yang dapat memperbarui proyek ini.');
        }

        if (! in_array($project->status, ProjectAccess::editableStatuses(), true)) {
            abort(403, 'Proyek dengan status ini tidak dapat diperbarui.');
        }

        $validated = $this->validateProjectForm($request, $project->status);
        $months = (int) $validated['planned_months'];

        $logoPath = ProjectAccess::storeProjectAttachment($request, $project->logo);

        $project->update([
            'name' => $validated['judul'],
            'group_name' => $validated['group_name'],
            'course_name' => $validated['course_name'],
            'description' => $this->buildDescription($validated),
            'logo' => $logoPath,
            'end_date' => now()->addMonths($months)->toDateString(),
            'planned_months' => $months,
            'lecturer_email' => strtolower($validated['lecturer_email']),
            'lecturer_name' => $validated['lecturer_name'],
        ]);

        $memberEmails = $this->parseMemberEmails($validated['member_emails'] ?? '');
        $skippedEmails = $this->workspace->syncProjectMembers(
            $project->fresh(),
            Auth::user(),
            $memberEmails
        );

        return $this->redirectAfterSave(
            $project->fresh(),
            $validated['action'],
            false,
            $skippedEmails
        );
    }

    public function destroyAPI(Request $request)
{
    try {

        $request->validate([
            'id' => ['required', 'integer'],
            'email' => ['required', 'email'],
        ]);

        $project = Project::findOrFail($request->id);

        $user = User::where(
            'email',
            strtolower($request->email)
        )->firstOrFail();

        if (! ProjectAccess::isProjectManager($project, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Project Manager yang dapat menghapus proyek ini.'
            ], 403);
        }

        $projectTitle = $project->title;

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyek "' . $projectTitle . '" berhasil dihapus.'
        ]);

    } catch (\Exception $e) {

        report($e);

        return response()->json([
            'success' => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'Gagal menghapus proyek.'
        ], 500);
    }
}

    public function destroy(int $id)
    {
        $project = Project::query()->findOrFail($id);

        if (! ProjectAccess::isProjectManager($project, (int) Auth::id())) {
            abort(403, 'Hanya Project Manager yang dapat menghapus proyek ini.');
        }

        $project->delete();

        return redirect()
            ->route('my-project')
            ->with('success', 'Proyek "'.$project->title.'" berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProjectForm(Request $request, ?string $projectStatus = 'draft'): array
    {
        $actions = $projectStatus === 'draft'
            ? 'draft,submit'
            : 'submit_revision';

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
            'action' => ['required', 'in:'.$actions],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildDescription(array $validated): string
    {
        return ProjectAccess::formatStoredDescription(
            (string) ($validated['deskripsi'] ?? ''),
            (string) ($validated['masalah'] ?? '')
        );
    }

    /**
     * @param  list<string>  $skippedEmails
     */
    private function redirectAfterSave(
        Project $project,
        string $action,
        bool $isNew,
        array $skippedEmails = []
    ): \Illuminate\Http\RedirectResponse {
        $memberNotice = $this->skippedMemberNotice($skippedEmails);

        if ($action === 'submit_revision') {
            $this->workspace->submitRevisionToLecturer($project->fresh());

            return redirect()
                ->route('problem-identification', $project->id)
                ->with('success', 'Perubahan diajukan ke dosen untuk disetujui kembali. Proyek tetap dapat diakses tim.')
                ->with('info', $memberNotice);
        }

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
                ->with('success', 'Proyek berhasil diajukan ke dosen. Status proyek: In Review.')
                ->with('info', $memberNotice);
        }

        $message = $isNew
            ? 'Proyek disimpan sebagai draft. Anda dapat melanjutkan pengisian atau mengajukan ke dosen nanti.'
            : 'Perubahan draft berhasil disimpan.';

        return redirect()
            ->route('problem-identification', $project->id)
            ->with('success', $message)
            ->with('info', $memberNotice);
    }

    /**
     * @param  list<string>  $skippedEmails
     */
    private function skippedMemberNotice(array $skippedEmails): ?string
    {
        if ($skippedEmails === []) {
            return null;
        }

        return 'Email berikut belum terdaftar di PjBL sehingga belum ditambahkan ke tim: '
            .implode(', ', $skippedEmails);
    }

    /**
     * @return list<string>
     */
    private function parseMemberEmails(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $email) => strtolower(trim($email)),
            $parts
        )));
    }
}
