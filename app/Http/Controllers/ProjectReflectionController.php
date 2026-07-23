<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectReflectionController extends Controller
{
    public static function defaultFields(): array
    {
        return [
            ['key' => 'experience', 'label' => 'Bagaimana pengalaman Anda selama mengerjakan proyek ini?', 'type' => 'textarea'],
            ['key' => 'achievement', 'label' => 'Hal apa yang paling Anda banggakan dari kontribusi Anda?', 'type' => 'textarea'],
            ['key' => 'obstacles', 'label' => 'Kendala apa yang Anda hadapi dan bagaimana cara mengatasinya?', 'type' => 'textarea'],
            ['key' => 'lesson', 'label' => 'Pembelajaran apa yang Anda peroleh dari proyek ini?', 'type' => 'textarea'],
        ];
    }

    public function save(Request $request, int $id)
    {
        $project = Project::findOrFail($id);
        abort_unless(ProjectAccess::userCanAccess((int) Auth::id(), $project), 403);
        abort_unless(DB::table('project_evaluations')->where('project_id', $id)->where('publication_status', 'published')->exists(), 403);

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable|string|max:3000',
            'action' => 'required|in:draft,submitted',
        ]);

        DB::table('project_reflections')->updateOrInsert(
            ['project_id' => $id, 'student_id' => Auth::id()],
            [
                'answers' => json_encode($validated['answers']),
                'status' => $validated['action'],
                'submitted_at' => $validated['action'] === 'submitted' ? now() : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', $validated['action'] === 'submitted' ? 'Refleksi berhasil dikirim.' : 'Draft refleksi disimpan.');
    }

    public function configure(Request $request, int $id)
    {
        $project = Project::findOrFail($id);
        abort_unless(Auth::user()?->role === 'lecturer' && ProjectAccess::lecturerCanView(Auth::user(), $project), 403);

        $validated = $request->validate([
            'fields' => 'required|array|min:1|max:20',
            'fields.*.key' => 'nullable|string|max:80',
            'fields.*.label' => 'required|string|max:500',
            'fields.*.type' => 'nullable|in:textarea',
        ]);

        $usedKeys = [];
        $fields = collect($validated['fields'])->map(function (array $field, int $index) use (&$usedKeys): array {
            $baseKey = str($field['key'] ?: $field['label'])->slug('_')->limit(60, '')->toString() ?: 'pertanyaan_'.($index + 1);
            $key = $baseKey;
            $suffix = 2;
            while (in_array($key, $usedKeys, true)) {
                $key = $baseKey.'_'.$suffix++;
            }
            $usedKeys[] = $key;

            return ['key' => $key, 'label' => trim($field['label']), 'type' => 'textarea'];
        })->values()->all();

        DB::table('project_reflection_forms')->updateOrInsert(
            ['project_id' => $id],
            ['fields' => json_encode($fields), 'updated_by' => Auth::id(), 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('dosen.penilaian', ['id' => $id, 'tab' => 'refleksi'])
            ->with('success', 'Pertanyaan refleksi berhasil diperbarui.');
    }
}
