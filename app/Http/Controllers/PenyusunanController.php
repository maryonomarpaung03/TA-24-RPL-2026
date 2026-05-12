<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use Carbon\Carbon;

class PenyusunanController extends Controller
{
    /*
    sementara pakai akun Maryono
    nanti tinggal Auth::id()
    */
    private $currentUserId = 2;

    /*
    ==========================================
    HALAMAN PENYUSUNAN
    ==========================================
    */
    public function index($id)
    {
        // Pastikan project milik Maryono
        $project = Project::where('id', $id)
            ->where('created_by', $this->currentUserId)
            ->first();

        if (!$project) {
            return redirect()
                ->route('projek-saya')
                ->with('error', 'Projek tidak ditemukan');
        }

       

        /*
        ambil task dari database
        */
        $taskData = DB::table('tasks')
            ->leftJoin(
                'users',
                'tasks.assigned_to',
                '=',
                'users.id'
            )
            ->where(
                'tasks.project_id',
                $project->id
            )
            ->select(
                'tasks.*',
                'users.full_name'
            )
            ->orderBy('tasks.created_at')
            ->get();

        /*
        format agar cocok dengan blade lama
        */
        $tasks = $taskData->map(
            function ($task, $index) {

                return [
                    'id' => $task->id,

                    'no' => $index + 1,

                    'judul' =>
                        $task->task_title,

                    'deskripsi' =>
                        $task->description
                        ?? '-',

                    'mulai' =>
                        $task->start_date
                        ? Carbon::parse(
                            $task->start_date
                        )->format('Y-m-d')
                        : '-',

                    'selesai' =>
                        $task->due_date
                        ? Carbon::parse(
                            $task->due_date
                        )->format('Y-m-d')
                        : '-',

                    'pj' =>
                        $task->full_name
                        ?? 'Belum Ditentukan',

                    'assigned_to' =>
                        $task->assigned_to
                ];
            }
        )->toArray();

        /*
        ambil semua user
        untuk dropdown penanggung jawab
        */
        $users = DB::table('users')
            ->select('id', 'full_name')
            ->get();

         return view(
            'Penyusunan',
            [
                'id' => $project->id,
                'namaProjek' => $project->title,
                'tasks' => $tasks,
                'users' => $users
            ]
        );
    }

    /*
    ==========================================
    TAMBAH TUGAS
    ==========================================
    */
   public function tambahTugas(
    Request $request,
    $id
)
{
    $request->validate([
        'judul_tugas' => 'required',
        'deskripsi_tugas' => 'nullable',
        'tanggal_mulai' => 'required',
        'tanggal_selesai' => 'required',
        'penanggung_jawab' => 'required'
    ]);

    $project = Project::where(
            'id',
            $id
        )
        ->where(
            'created_by',
            $this->currentUserId
        )
        ->first();

    if (!$project) {
        return back()->with(
            'error',
            'Project tidak ditemukan'
        );
    }

   
    $milestone = DB::table('milestones')
    ->first();

if (!$milestone) {

    return back()->with(
        'error',
        'Belum ada milestone pada database'
    );
}

$milestoneId =
    $milestone->id;


    DB::table('tasks')->insert([

        'project_id' =>
            $project->id,

        'milestone_id' =>
            $milestoneId,

        'parent_task_id' =>
            null,

        'assigned_to' =>
            $request->penanggung_jawab,

        'task_title' =>
            $request->judul_tugas,

        'description' =>
            $request->deskripsi_tugas,

        'priority' =>
            'medium',

        'status' =>
            'pending',

        'progress_percent' =>
            0,

        'start_date' =>
            $request->tanggal_mulai,

        'due_date' =>
            $request->tanggal_selesai,

        'created_at' =>
            now()
    ]);

    return back()->with(
        'success',
        'Tugas berhasil ditambah'
    );
}

    /*
    ==========================================
    EDIT TUGAS
    ==========================================
    */
    public function editTugas(
        Request $request,
        $id
    )
    {
        $request->validate([
            'task_id' => 'required',
            'judul_tugas' => 'required',
            'deskripsi_tugas' => 'nullable',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'required'
        ]);

        DB::table('tasks')
            ->where(
                'id',
                $request->task_id
            )
            ->update([

                'task_title' =>
                    $request->judul_tugas,

                'description' =>
                    $request->deskripsi_tugas,

                'start_date' =>
                    $request->tanggal_mulai,

                'due_date' =>
                    $request->tanggal_selesai
            ]);

        return back()->with(
            'success',
            'Tugas berhasil diubah'
        );
    }

    /*
    ==========================================
    HAPUS TUGAS
    ==========================================
    */
    public function hapusTugas(
        Request $request,
        $id
    )
    {
        DB::table('tasks')
            ->where(
                'id',
                $request->task_id
            )
            ->delete();

        return back()->with(
            'success',
            'Tugas berhasil dihapus'
        );
    }

    /*
    ==========================================
    KOMENTAR TUGAS
    ==========================================
    */
    public function komentarTugas(
        Request $request,
        $id
    )
    {
        $request->validate([
            'task_id' => 'required',
            'komentar' => 'required'
        ]);

        DB::table('discussions')
            ->insert([

                'project_id' =>
                    $id,

                'user_id' =>
                    $this->currentUserId,

                'task_id' =>
                    $request->task_id,

                'message' =>
                    $request->komentar,

                'created_at' =>
                    now()
            ]);

        return back()->with(
            'success',
            'Komentar berhasil diberikan'
        );
    }
}