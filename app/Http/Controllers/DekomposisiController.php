<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\StageProgressService;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DekomposisiController extends Controller
{
    public function index($id)
    {
        $selected =
            ProjectCatalog::find($id);

        if (!$selected) {

            return redirect()
                ->route('projek-saya')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $user =
            Auth::user();

        $displayName =
            $user?->full_name
            ?? $user?->name
            ?? 'User';

        $initials =
            ProjectAccess::initialsFromName(
                $displayName
            );

        /*
        =====================================
        APPROVED PROBLEM STATEMENTS
        =====================================
        */
        $approvedProblems = DB::table('problem_identifications')
            ->where('project_id', $id)
            ->where('board_status', 'done')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'title'       => $p->title,
                'description' => $p->description ?? null,
                'category'    => $p->category ?? null,
                'priority'    => $p->priority ?? null,
            ])
            ->values()
            ->toArray();

        /*
        =====================================
        LOAD NODES
        =====================================
        */
        $nodes =
            DB::table(
                'decomposition_nodes'
            )
            ->where(
                'project_id',
                $id
            )
            ->get()
            ->map(function ($node) {

                return [
                    'key' =>
                        $node->node_key,

                    'title' =>
                        $node->title,

                    'shape' =>
                        $node->shape,

                    'color' =>
                        $node->color,

                    'createdBy' =>
                        $node->created_by,

                    'createdAt' =>
                        $node->created_at_label,

                    'x' =>
                        (float)
                        $node->pos_x,

                    'y' =>
                        (float)
                        $node->pos_y,
                ];
            })
            ->values()
            ->toArray();

        /*
        =====================================
        ROOT DEFAULT
        =====================================
        */
        if (empty($nodes)) {

            $nodes[] = [

                'key' =>
                    'root',

                'title' =>
                    $approvedProblems[0]['title']
                    ?? $selected['title']
                    ?? $selected['name']
                    ?? 'Topik Utama',

                'shape' =>
                    'circle',

                'color' =>
                    '#dbeafe',

                'createdBy' =>
                    $displayName,

                'createdAt' =>
                    now()->format(
                        'd M Y'
                    ),

                'x' => 420,
                'y' => 180,
            ];
        }

        /*
        =====================================
        LOAD CONNECTIONS
        =====================================
        */
        $connections =
            DB::table(
                'decomposition_connections'
            )
            ->where(
                'project_id',
                $id
            )
            ->get([
                'from_node as from',
                'to_node as to'
            ])
            ->toArray();

        /*
        =====================================
        LOAD COMMENTS
        =====================================
        */
        $comments =
            DB::table(
                'decomposition_comments'
            )
            ->where(
                'project_id',
                $id
            )
            ->orderBy(
                'created_at'
            )
            ->get()
            ->map(function ($comment) {

                return [
                    'id' =>
                        $comment->id,

                    'author' =>
                        $comment
                        ->author_initials,

                    'text' =>
                        $comment
                        ->comment_text,
                ];
            })
            ->values()
            ->toArray();

        return view(
            'Dekomposisi',
            [

                'id' =>
                    $id,

                'namaProjek' =>
                    $selected['title']
                    ?? $selected['name']
                    ?? 'Proyek',

                'user' => [

                    'name' =>
                        $displayName,

                    'role' =>
                        $user?->role
                        ?? 'student',

                    'initials' =>
                        $initials,

                    'notif_count' =>
                        1,
                ],

                'selected_project' =>
                    $selected,

                'approvedProblems' => $approvedProblems,

                'diagramSeed' => [

                    'nodes' =>
                        $nodes,

                    'connections' =>
                        $connections,

                    'comments' =>
                        $comments
                ]
            ]
        );
    }

    public function sync(
        Request $request,
        $id,
        StageProgressService $stages
    )
    {
        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return response()->json(['ok' => false, 'message' => 'Akses ditolak.'], 403);
        }

        /*
        Tahap yang sudah difinalisasi hanya dapat dibaca. Klien menyimpan diagram
        secara otomatis pada setiap perubahan, jadi tanpa penjagaan ini autosave
        masih bisa menimpa diagram yang sudah dikirim ke dosen.
        */
        if ($stages->isFinalized((int) $project->id, StageProgressService::DECOMPOSITION)) {
            return response()->json([
                'ok'      => false,
                'locked'  => true,
                'message' => 'Tahapan Dekomposisi sudah difinalisasi dan hanya dapat dibaca.',
            ], 423);
        }

        $this->persistDiagram(
            $id,
            $request->input('nodes', []),
            $request->input('connections', []),
            $request->input('comments', [])
        );

        return response()
            ->json([
                'ok' => true
            ]);
    }

    /**
     * Kirim diagram ke dosen. Pengiriman ini sekaligus menutup tahap Decomposition:
     * tim tidak perlu menekan "Finalisasi Tahap" secara terpisah.
     */
    public function submit(Request $request, $id, StageProgressService $stages)
    {
        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return response()->json(['ok' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Mengirim diagram sekaligus memfinalisasi tahap, jadi tahap yang sudah
        // terkunci tidak boleh dikirim ulang.
        if ($stages->isFinalized((int) $project->id, StageProgressService::DECOMPOSITION)) {
            return response()->json([
                'ok'      => false,
                'locked'  => true,
                'message' => 'Diagram sudah dikirim dan tahapan Dekomposisi telah difinalisasi.',
            ], 423);
        }

        $nodes       = $request->input('nodes', []);
        $connections = $request->input('connections', []);
        $comments    = $request->input('comments', []);

        if (empty($nodes)) {
            return response()->json(['ok' => false, 'message' => 'Diagram masih kosong.'], 422);
        }

        $userId = (int) Auth::id();

        DB::transaction(function () use ($project, $userId, $nodes, $connections, $comments, $stages) {
            // Simpan dulu diagram yang dikirim. Autosave berjalan asinkron, jadi tanpa
            // ini perubahan terakhir bisa belum sampai ke DB saat tahap dikunci — dan
            // setelah terkunci autosave ditolak, sehingga perubahan itu hilang. Ini
            // juga yang membuat diagram yang dilihat dosen sama persis dengan snapshot
            // dan dengan ringkasan tahap.
            $this->persistDiagram((int) $project->id, $nodes, $connections, $comments);

            DB::table('decomposition_submissions')->insert([
                'project_id'           => $project->id,
                'submitted_by'         => $userId,
                'nodes_snapshot'       => json_encode($nodes),
                'connections_snapshot' => json_encode($connections),
                'comments_snapshot'    => json_encode($comments),
                'status'               => 'submitted',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $this->notifyLecturers($project);

            // Diagram terkirim = tahap Decomposition selesai. Dikunci setelah
            // snapshot tersimpan supaya ringkasan tahap ikut mencerminkan diagram ini.
            $stages->finalizeOnDiagramSubmission($project, $userId);
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Diagram berhasil dikirim ke dosen. Tahapan Dekomposisi difinalisasi.',
        ]);
    }

    /**
     * Tulis ulang isi diagram (node, koneksi, komentar) sebuah proyek. Node yang
     * tidak lagi dikirim klien dianggap terhapus. Dipakai oleh autosave (sync);
     * dibungkus transaksi supaya kegagalan di tengah tidak menyisakan diagram
     * yang separuh tertulis.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $connections
     * @param  array<int, array<string, mixed>>  $comments
     */
    private function persistDiagram(int|string $id, array $nodes, array $connections, array $comments): void
    {
        DB::transaction(function () use ($id, $nodes, $connections, $comments) {
            $keys = [];

            foreach ($nodes as $node) {
                $key = $node['key'] ?? null;

                if (! $key) {
                    continue;
                }

                $keys[] = $key;

                DB::table('decomposition_nodes')->updateOrInsert(
                    [
                        'project_id' => $id,
                        'node_key'   => $key,
                    ],
                    [
                        'title'            => $node['title'] ?? null,
                        'shape'            => $node['shape'] ?? 'rounded',
                        'color'            => $node['color'] ?? '#dbeafe',
                        'created_by'       => $node['createdBy'] ?? null,
                        'created_at_label' => $node['createdAt'] ?? null,
                        'pos_x'            => $node['x'] ?? 0,
                        'pos_y'            => $node['y'] ?? 0,
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );
            }

            DB::table('decomposition_nodes')
                ->where('project_id', $id)
                ->whereNotIn('node_key', $keys)
                ->delete();

            DB::table('decomposition_connections')->where('project_id', $id)->delete();

            foreach ($connections as $connection) {
                DB::table('decomposition_connections')->insert([
                    'project_id' => $id,
                    'from_node'  => $connection['from'] ?? '',
                    'to_node'    => $connection['to'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('decomposition_comments')->where('project_id', $id)->delete();

            $authorName = Auth::user()?->full_name ?? Auth::user()?->name;

            foreach ($comments as $comment) {
                DB::table('decomposition_comments')->insert([
                    'project_id'      => $id,
                    'author_name'     => $authorName,
                    'author_initials' => $comment['author'] ?? 'U',
                    'comment_text'    => $comment['text'] ?? '',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        });
    }

    /**
     * Dosen pembimbing proyek: yang tercatat di proyek dan yang mengampu kelasnya.
     * project_members hanya berisi anggota tim, jadi kelas dibaca lewat
     * projects.academic_class_id.
     */
    private function notifyLecturers(Project $project): void
    {
        $emails = collect([$project->lecturer_email]);

        if ($project->academic_class_id) {
            $emails->push(
                DB::table('academic_classes')
                    ->join('users', 'users.id', '=', 'academic_classes.lecturer_id')
                    ->where('academic_classes.id', $project->academic_class_id)
                    ->value('users.email')
            );
        }

        $emails = $emails
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique();

        $submitterName = Auth::user()?->full_name ?? Auth::user()?->name ?? 'Mahasiswa';

        foreach ($emails as $email) {
            DB::table('project_notifications')->insert([
                'project_id'      => $project->id,
                'recipient_email' => $email,
                'type'            => 'decomposition_submitted',
                'title'           => 'Diagram Dekomposisi Dikirim',
                'message'         => $submitterName.' mengirimkan diagram dekomposisi proyek "'.$project->title.'" untuk ditinjau.',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
