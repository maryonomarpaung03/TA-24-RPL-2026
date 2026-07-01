<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
        $id
    )
    {
        /*
        =====================================
        SAVE NODES
        =====================================
        */
        $nodes =
            $request->nodes
            ?? [];

        $keys = [];

        foreach (
            $nodes
            as $node
        ) {

            $key =
                $node['key']
                ?? null;

            if (!$key) {
                continue;
            }

            $keys[] =
                $key;

            DB::table(
                'decomposition_nodes'
            )->updateOrInsert(

                [
                    'project_id' =>
                        $id,

                    'node_key' =>
                        $key
                ],

                [
                    'title' =>
                        $node['title']
                        ?? null,

                    'shape' =>
                        $node['shape']
                        ?? 'rounded',

                    'color' =>
                        $node['color']
                        ?? '#dbeafe',

                    'created_by' =>
                        $node['createdBy']
                        ?? null,

                    'created_at_label' =>
                        $node['createdAt']
                        ?? null,

                    'pos_x' =>
                        $node['x']
                        ?? 0,

                    'pos_y' =>
                        $node['y']
                        ?? 0,

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now(),
                ]
            );
        }

        /*
        DELETE REMOVED NODES
        */
        DB::table(
            'decomposition_nodes'
        )
        ->where(
            'project_id',
            $id
        )
        ->whereNotIn(
            'node_key',
            $keys
        )
        ->delete();

        /*
        =====================================
        SAVE CONNECTIONS
        =====================================
        */
        DB::table(
            'decomposition_connections'
        )
        ->where(
            'project_id',
            $id
        )
        ->delete();

        foreach (
            $request->connections
            ?? []
            as $connection
        ) {

            DB::table(
                'decomposition_connections'
            )->insert([

                'project_id' =>
                    $id,

                'from_node' =>
                    $connection['from']
                    ?? '',

                'to_node' =>
                    $connection['to']
                    ?? '',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
        }

        /*
        =====================================
        SAVE COMMENTS
        =====================================
        */
        DB::table(
            'decomposition_comments'
        )
        ->where(
            'project_id',
            $id
        )
        ->delete();

        foreach (
            $request->comments
            ?? []
            as $comment
        ) {

            DB::table(
                'decomposition_comments'
            )->insert([

                'project_id' =>
                    $id,

                'author_name' =>
                    Auth::user()
                    ?->full_name
                    ?? Auth::user()
                    ?->name,

                'author_initials' =>
                    $comment['author']
                    ?? 'U',

                'comment_text' =>
                    $comment['text']
                    ?? '',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
        }

        return response()
            ->json([
                'ok' => true
            ]);
    }

    public function submit(Request $request, $id)
    {
        $project = Project::query()->find($id);

        if (! $project || ! ProjectAccess::userCanAccess((int) Auth::id(), $project)) {
            return response()->json(['ok' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $nodes       = $request->input('nodes', []);
        $connections = $request->input('connections', []);
        $comments    = $request->input('comments', []);

        if (empty($nodes)) {
            return response()->json(['ok' => false, 'message' => 'Diagram masih kosong.'], 422);
        }

        DB::table('decomposition_submissions')->insert([
            'project_id'          => $project->id,
            'submitted_by'        => Auth::id(),
            'nodes_snapshot'      => json_encode($nodes),
            'connections_snapshot' => json_encode($connections),
            'comments_snapshot'   => json_encode($comments),
            'status'              => 'submitted',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Kirim notifikasi ke dosen yang terhubung ke proyek ini
        $lecturerEmails = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $project->id)
            ->where('users.role', 'lecturer')
            ->pluck('users.email');

        // Cek juga dosen dari kelas (academic_classes)
        $classLecturerEmails = DB::table('academic_classes')
            ->join('users', 'academic_classes.lecturer_id', '=', 'users.id')
            ->whereIn('academic_classes.id', function ($q) use ($project) {
                $q->select('class_id')
                    ->from('project_members')
                    ->where('project_id', $project->id);
            })
            ->pluck('users.email');

        $allLecturerEmails = $lecturerEmails->merge($classLecturerEmails)->unique();

        $submitterName = Auth::user()?->full_name ?? Auth::user()?->name ?? 'Mahasiswa';

        foreach ($allLecturerEmails as $email) {
            DB::table('project_notifications')->insert([
                'project_id'      => $project->id,
                'recipient_email' => strtolower(trim($email)),
                'type'            => 'decomposition_submitted',
                'title'           => 'Diagram Dekomposisi Dikirim',
                'message'         => $submitterName . ' mengirimkan diagram dekomposisi proyek "' . $project->title . '" untuk ditinjau.',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'Diagram berhasil dikirim ke dosen.']);
    }
}